# Laravel AI 0.11 to 1.0 Upgrade Specialist

You are an expert Laravel AI upgrade specialist with deep knowledge of both Laravel AI 0.11 and 1.0. Your task is to systematically upgrade the application from `laravel/ai` 0.11 to 1.0 while ensuring all functionality remains intact. You understand the nuances of breaking changes and can identify affected code patterns with precision.

## Core Principle: Documentation-First Approach

**IMPORTANT:** Always use the `search-docs` tool whenever you need:
- Specific code examples for implementing Laravel AI 1.0 features
- Clarification on breaking changes or new syntax
- Verification of upgrade patterns before applying them
- Examples of correct usage for new classes or methods

The official Laravel AI documentation is your primary source of truth. Consult it before making assumptions or implementing changes.

## Upgrade Process

Follow this systematic process to upgrade the application:

### 1. Assess Current State

Before making any changes:

- Check `composer.json` for the current `laravel/ai` version constraint
- Run `{{ $assist->composerCommand('show laravel/ai') }}` to confirm the installed version
- Review `config/ai.php` for current configuration
- Identify every agent, tool, middleware, and custom provider in the application
- Determine whether conversations are persisted (the `agent_conversation_messages` table has rows) because that decides whether a backfill migration is required

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- **Back up the conversation tables** - the backfill migration rewrites every stored assistant message and drops columns

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by 1.0 changes:

**High Priority Searches:**
- `tool_calls` or `tool_results` - Columns replaced by a single `steps` column
- `approval_state` or `approvalState` - Column and property replaced by a `status` enum
- `->toolCalls` or `->toolResults` on `StoredMessage` - Now methods, not properties
- `promptTokens` or `completionTokens` - Renamed to `inputTokens` and `outputTokens`
- `AgentPrompt $prompt` inside middleware `handle()` - Middleware now receives a `PendingStep`
- `aws/aws-sdk-php` usage with the Bedrock provider - No longer installed by default
- `addFile(` on a Gemini vector store - Now blocks until import completes and returns a different ID

**Medium Priority Searches:**
- `->tokens` on an `EmbeddingsResponse` - Replaced by `->usage->inputTokens`
- `new Usage(` - Text responses now use `TextUsage` with a different argument order
- `usingVercelDataProtocol(true` - The boolean first argument was removed
- `toVercelProtocolArray(` or `CanStreamUsingVercelProtocol` - Removed
- `instanceof ToolResult` in stream consumers - Sub-agent runs now emit preliminary results
- Transcript rendering or message counting - Resumed turns fold into one message, and failed turns are now stored
- `TextStart` / `TextEnd` handling - Now one pair per step instead of per content block

**Low Priority Searches:**
- `pausedProviderContentBlocks(` - Read `$response->steps` instead
- `providerContentBlocks` - Renamed to `replayBlocks`
- `implements ConversationStore` - Four method signatures changed
- `implements RemembersConversations` or `implements Agent` - Contracts gained methods and wider types
- `meta.reasoning`, `provider_steps`, or `provider_content_blocks` - Moved onto the steps
- `new Step(`, `new StructuredStep(`, or `new ToolApprovalRequest(` - Constructor signatures changed
- Custom providers or gateways - Several protected hooks and signatures changed

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **Consult documentation** - Use `search-docs` tool to verify correct upgrade patterns and examples
3. **List** all files that need modification
4. **Apply** the fix consistently across all occurrences
5. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete:

- `{{ $assist->composerCommand('require laravel/ai:^1.0') }}`
- `{{ $assist->composerCommand('require aws/aws-sdk-php') }}` (only if the application uses the Bedrock provider)
- Write and run the backfill migration below if conversations are already persisted

### 6. Test and Verify

- Run the full test suite
- Replay a stored conversation to confirm the backfilled `steps` column reads correctly
- Exercise a tool-approval flow, a streamed run, and any run using an `AgentTool`
- Re-check any cost or token reporting, because usage totals are now inclusive

## Execution Strategy

When upgrading, maximize efficiency by:

- **Batch similar changes** - Group all usage renames, then all middleware changes, etc.
- **Use parallel agents** for independent file modifications
- **Prioritize high-impact changes** that could cause immediate failures
- **Test incrementally** - Verify after each category of changes

---

# Upgrading To 1.0 From 0.11

## High-impact changes

### Conversation messages store steps

The `tool_calls` and `tool_results` columns on the `agent_conversation_messages` table have been replaced by a single `steps` column. Each assistant message now stores one entry per model round-trip, and each tool result is stored on the tool call that produced it:

@boostsnippet('Steps Column Shape', 'json')
[
    {"content": "", "tool_calls": [{"id": "call_1", "name": "read_file", "arguments": {}, "result": "..."}], "reasoning": "", "replay_blocks": [], "provider_tool_calls": []},
    {"content": "Done.", "tool_calls": [], "reasoning": "", "replay_blocks": [], "provider_tool_calls": []}
]
@endboostsnippet

The `participant_index` on the same table now also includes the `agent` column.

The `approval_state` column has been replaced by a `status` column holding a `Laravel\Ai\Enums\MessageStatus` value: `completed`, `paused`, or `failed`. The reason a call is waiting on a decision is now stored on the call itself as `approval_reason`, so a stored call carrying that key without a `result` is one still pending:

@boostsnippet('Pending Tool Call Shape', 'json')
{"id": "call_1", "name": "delete_file", "arguments": {"path": "a"}, "approval_reason": "Destructive."}
@endboostsnippet

The package's existing migration will not run again during an upgrade. If you have already migrated the conversation tables, create a new migration containing the code below, then run `{{ $assist->artisanCommand('migrate') }}` before deploying the new version of your application. The migration adds the `steps` and `status` columns, rewrites every existing row, and drops the old columns.

@boostsnippet('Backfill Migration', 'php')
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    public function up(): void
    {
        $table = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
            $blueprint->longText('steps')->nullable();
            $blueprint->string('status', 25)->default(MessageStatus::Completed->value);
        });

        $this->query($table)->where('role', 'user')->update(['steps' => '[]']);

        $this->query($table)
            ->select('conversation_id')
            ->distinct()
            ->orderBy('conversation_id')
            ->chunk(100, function (Collection $conversations) use ($table) {
                foreach ($conversations as $conversation) {
                    $this->backfillConversation($table, $conversation->conversation_id);
                }
            });

        Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
            $blueprint->longText('steps')->nullable(false)->change();
            $blueprint->dropColumn(['tool_calls', 'tool_results', 'approval_state']);
            $blueprint->dropIndex('participant_index');
            $blueprint->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
        });
    }

    protected function backfillConversation(string $table, string $conversationId): void
    {
        $rows = $this->query($table)
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderBy('id')
            ->get();

        // Results are gathered across the conversation because an approval resolved on a later request used to be recorded on that request's row...
        $results = [];
        $pending = [];

        foreach ($rows as $row) {
            foreach ($this->decoded($row->tool_results) as $result) {
                if (isset($result['id'])) {
                    $results[$result['id']] ??= $result;
                }
            }

            $pending = [...$pending, ...$this->decoded($row->approval_state)['pending'] ?? []];
        }

        foreach ($rows as $row) {
            [$steps, $meta] = $this->stepsFrom($row);

            $steps = array_map(function (array $step) use ($results, $pending): array {
                $toolCalls = [];

                foreach ($step['tool_calls'] as $toolCall) {
                    $result = $results[$toolCall['id'] ?? ''] ?? null;

                    $awaiting = array_key_exists($toolCall['id'] ?? '', $pending);

                    if ($result === null && ! $awaiting) {
                        continue;
                    }

                    $toolCalls[] = [
                        ...$toolCall,
                        ...$awaiting ? ['approval_reason' => $pending[$toolCall['id']]] : [],
                        ...$result === null ? [] : [
                            'result' => $result['result'] ?? null,
                            ...array_filter(['denied' => $result['denied'] ?? false, 'failed' => $result['failed'] ?? false]),
                        ],
                    ];
                }

                $step['tool_calls'] = $toolCalls;

                return $step;
            }, $steps);

            $this->query($table)->where('id', $row->id)->update([
                'steps' => json_encode($steps),
                'meta' => json_encode($meta),
                'status' => blank($this->decoded($row->approval_state)['pending'] ?? []) ? MessageStatus::Completed : MessageStatus::Paused,
            ]);
        }
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function stepsFrom(object $row): array
    {
        $meta = $this->decoded($row->meta);
        $calls = array_values($this->decoded($row->tool_calls));

        $providerSteps = $meta['provider_steps'] ?? null;

        if (is_array($providerSteps) && $providerSteps !== []) {
            $steps = [];

            foreach ($providerSteps as $providerStep) {
                $ids = $providerStep['tool_call_ids'] ?? [];

                $steps[] = [
                    'content' => '',
                    'tool_calls' => array_values(array_filter($calls, fn (array $call) => in_array($call['id'] ?? null, $ids, true))),
                    'reasoning' => '',
                    'replay_blocks' => $providerStep['blocks'] ?? [],
                    'provider_tool_calls' => [],
                ];
            }
        } else {
            $steps = [[
                'content' => '',
                'tool_calls' => $calls,
                'reasoning' => '',
                'replay_blocks' => $meta['provider_content_blocks'] ?? [],
                'provider_tool_calls' => [],
            ]];

            // A completed turn's text was produced after its results, so it replays as a step of its own...
            if ($calls !== [] && $row->approval_state === null && (string) $row->content !== '') {
                $steps[] = ['content' => '', 'tool_calls' => [], 'reasoning' => '', 'replay_blocks' => [], 'provider_tool_calls' => []];
            }
        }

        // Raw provider blocks are replayed only while a turn is paused, so a completed turn keeps none...
        if ($row->approval_state === null) {
            $steps = array_map(fn (array $step): array => [...$step, 'replay_blocks' => []], $steps);
        }

        $steps[array_key_last($steps)]['content'] = (string) $row->content;
        $steps[array_key_last($steps)]['reasoning'] = (string) ($meta['reasoning'] ?? '');

        unset($meta['provider_steps'], $meta['provider_content_blocks'], $meta['reasoning']);

        return [$steps, $meta];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decoded(?string $json): array
    {
        return is_array($decoded = json_decode($json ?? '', true)) ? $decoded : [];
    }

    protected function query(string $table): Builder
    {
        return DB::connection($this->getConnection())->table($table);
    }
};
@endboostsnippet

If you run raw queries against the conversation tables, update them to read the `steps` column instead of `tool_calls` or `tool_results`. The `tool_calls` and `tool_results` attributes on the `Laravel\Ai\Models\ConversationMessage` model are still available as read-only attributes, and a `provider_tool_calls` attribute has been added. To modify a stored message, write to the `steps` attribute instead.

If you read a message's reasoning or replay state from the `meta` column, update your code to read it from the steps instead:

- `meta.reasoning` is now `steps[].reasoning`
- `meta.provider_steps` and `meta.provider_content_blocks` are now `steps[].replay_blocks`

Replay blocks are only retained while a turn is paused for tool approval and are cleared when the turn completes. Each stored tool call contains only the `id`, `name`, `arguments`, `result`, `result_id`, `denied`, and `failed` keys, plus `approval_reason` when the call was gated behind an approval and `thought_signature` when Gemini provides one. Provider-specific reasoning keys such as `reasoning_id` and `reasoning_encrypted_content` are no longer stored.

If you use the `Laravel\Ai\Storage\StoredMessage` class, replace the removed `$toolCalls` and `$toolResults` properties with the new methods:

@boostsnippet('StoredMessage Accessors', 'php')
// Before...
$message->toolCalls;
$message->toolResults;

// After...
$message->toolCalls();
$message->toolResults();
$message->providerToolCalls();
@endboostsnippet

Its `$approvalState` array has also been replaced by a `$status` enum, and `toArray()` emits a `status` key in place of `approval_state`. Read the pending calls from the steps instead:

@boostsnippet('Reading Pending Approvals', 'php')
// Before...
$message->approvalState['pending'];

// After...
array_filter($message->toolCalls(), fn (array $call) => PendingApproval::isPending($call));
@endboostsnippet

The `approval_state` cast on the `Laravel\Ai\Models\ConversationMessage` model has been replaced by a `status` cast to the same enum.

The `StoredMessage` constructor now accepts a `steps` argument in place of `toolCalls` and `toolResults`, and `toArray()` emits a `steps` key in their place. Update any code that constructs a `StoredMessage` manually.

### Agent middleware wraps each generation step

Agent middleware now wraps each generation step instead of the whole run, and receives a `Laravel\Ai\PendingStep` instead of an `AgentPrompt`. A run that takes three steps invokes your middleware three times.

@boostsnippet('Middleware Signature', 'php')
// Before...
use Closure;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogTheRun
{
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        return $next($prompt)->then(function (AgentResponse $response) {
            // ...
        });
    }
}

// After...
use Closure;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\PendingStep;

class LogTheRun
{
    public function handle(PendingStep $step, Closure $next)
    {
        return $next($step)->then(function (StepResponse $response) {
            // ...
        });
    }
}
@endboostsnippet

Modify a step by creating a copy before passing it to the next middleware:

@boostsnippet('Modifying A Step', 'php')
public function handle(PendingStep $step, Closure $next)
{
    if (! $step->isFirstStep()) {
        $step = $step->withoutTools('SearchDocumentation');
    }

    return $next($step);
}
@endboostsnippet

The `withModel()`, `withInstructions()`, `withMessages()`, `withTools()`, `onlyTools()`, `withoutTools()`, `withToolChoice()`, `withMaxTokens()`, and `withProviderOptions()` methods are available, along with `isFirstStep()` and the `$isFinalStep` property.

Return the `Laravel\Ai\Gateway\StepResult` returned by `$next($step)`, or return a `StepResponse` to answer the step without calling the model. Anything else throws a `LogicException`.

The `AgentPrompted`, `AgentStreamed`, and `AgentFailed` events now carry the original `AgentPrompt` passed to the provider rather than a prompt modified by run middleware. If you relied on a listener receiving the modified prompt, move that logic into the middleware itself.

### Gemini vector store imports wait for completion

Adding a file to a Gemini vector store now waits for the import to finish instead of returning as soon as it is requested. The returned ID is now the document name rather than the import operation name, so IDs stored by an earlier version no longer match. If you persist these IDs, re-import the affected files. The call now throws a `Laravel\Ai\Exceptions\AiException` when the import fails or does not finish within five minutes.

### The AWS SDK is no longer installed by default

The `aws/aws-sdk-php` package is no longer a required dependency. If you use the Bedrock provider, install it with `{{ $assist->composerCommand('require aws/aws-sdk-php') }}`. Resolving the Bedrock provider without the SDK installed throws a `RuntimeException`.

### Token usage is reported inclusively

`Usage::$promptTokens` and `Usage::$completionTokens` have been renamed:

@boostsnippet('Usage Property Renames', 'php')
// Before...
$response->usage->promptTokens;
$response->usage->completionTokens;

// After...
$response->usage->inputTokens;
$response->usage->outputTokens;
@endboostsnippet

The new properties carry the provider's full counts. `inputTokens` now includes cached and cache-written tokens, and `outputTokens` now includes reasoning tokens. Previously these were reported separately and excluded from the totals.

If you previously calculated input token costs by applying a single rate to `promptTokens`, calculate each category separately:

@boostsnippet('Calculating Input Cost', 'php')
$usage = $response->usage;

$cost = $usage->uncachedInputTokens() * $baseRate
    + ($usage->cacheReadInputTokens ?? 0) * $cacheReadRate
    + ($usage->cacheWriteInputTokens ?? 0) * $cacheWriteRate;
@endboostsnippet

`toArray()` and the JSON stored in the `usage` column of the `agent_conversation_messages` table now use the `input_tokens` and `output_tokens` keys. Rows written before the upgrade keep the old keys, so read both when reporting on historical rows.

Reported values have also changed in three places:

- Anthropic streams read the cumulative usage reported on `message_delta`, so a run using a server tool such as web search reports a higher input token count than before
- Anthropic populates `reasoningTokens` from the thinking token breakdown rather than always reporting `0`
- Cohere embeddings on Bedrock report the input token count returned by the API rather than always reporting `0`

## Medium-impact changes

### Resumed turns fold into the message they paused on

Resuming a paused turn now appends the steps the resumed run made to the assistant message the turn paused on, rather than storing a second assistant message. The turn's usage is summed and its citations are merged, and `storeAssistantMessage()` returns the ID of the message it folded into.

A conversation that paused for an approval therefore holds one assistant message per turn instead of one per request. If you render a transcript or count messages, expect the resumed half of a turn to appear on the message that requested the approval.

### Failed turns are recorded

A remembered run that throws now stores the steps it completed before it died, as an assistant message with a `failed` status carrying the error message in `meta.error`. Previously the turn was lost and the conversation kept only the user message.

The turn is recorded once the run is out of providers to fail over to, so a run that fails over and then succeeds stores only the successful turn. A run that died before its first step stores nothing, unless it was resuming a paused turn, which is failed in place.

If you render a transcript or count messages, expect an assistant message where a failed run previously left none. Filter them out by status:

@boostsnippet('Filtering Failed Turns', 'php')
$conversation->messages()->where('status', MessageStatus::Completed);
@endboostsnippet

Streamed runs report their failure through a new `catch()` callback on `StreamableAgentResponse`, which receives the exception before it is rethrown.

### Text responses report a `TextUsage` object

The `Laravel\Ai\Responses\Data\Usage` class now holds only `inputTokens` and `outputTokens`. The `cacheReadInputTokens`, `cacheWriteInputTokens`, and `reasoningTokens` properties, along with the `add()` and `uncachedInputTokens()` methods, have moved to a new `Laravel\Ai\Responses\Data\TextUsage` subclass. Text, agent, step, and stream responses report a `TextUsage` object.

No changes are needed if you only read usage from a response. If you construct a `TextResponse`, `StepResponse`, `Step`, or `StreamEnd` by hand, such as a fake in a test suite, pass a `TextUsage` instance and note the new argument order:

@boostsnippet('TextUsage Construction', 'php')
// Before...
use Laravel\Ai\Responses\Data\Usage;

new Usage($promptTokens, $completionTokens, $cacheWriteInputTokens, $cacheReadInputTokens, $reasoningTokens);

// After...
use Laravel\Ai\Responses\Data\TextUsage;

new TextUsage($inputTokens, $outputTokens, $cacheReadInputTokens, $cacheWriteInputTokens, $reasoningTokens);
@endboostsnippet

The cache read and cache write arguments have swapped positions. The three optional counts are now `?int` and are `null` when the provider does not report them, so use the null coalescing operator when treating them as numbers.

### Usage is reported on every response

The `EmbeddingsResponse::$tokens` property has been removed in favor of a `$usage` object, matching the other response types:

@boostsnippet('Embeddings Usage', 'php')
// Before...
$response->tokens;

// After...
$response->usage->inputTokens;
@endboostsnippet

`EmbeddingsResponse::toArray()` and `jsonSerialize()` now emit a `usage` object in place of the `tokens` integer. `AudioResponse` and `RerankingResponse` now carry a `$usage` property as well. Each capability reports its relevant billing metrics through its usage class:

- `ImageResponse::$usage` is an `ImageUsage`, adding `imageInputTokens` and `imageOutputTokens`
- `TranscriptionResponse::$usage` is a `TranscriptionUsage`, adding `audioSeconds`
- `RerankingResponse::$usage` is a `RerankingUsage`, adding `searchUnits`
- `AudioResponse::$usage` and `EmbeddingsResponse::$usage` are plain `Usage` instances

The added counts are `null` when the provider does not report them. No changes are needed unless you read `EmbeddingsResponse::$tokens` or construct these responses by hand. `EmbeddingsResponse`, `AudioResponse`, and `RerankingResponse` take their respective usage object as the second constructor argument, before the `Meta`. `ImageResponse` takes an `ImageUsage` as its second argument, before the `Meta`, while `TranscriptionResponse` takes a `TranscriptionUsage` as its third argument, after the text and segments and before the `Meta`.

### Stream protocols

Stream protocols are now objects implementing `Laravel\Ai\Streaming\Protocols\StreamProtocol` rather than a flag on the response. The `Laravel\Ai\Responses\Concerns\CanStreamUsingVercelProtocol` trait and the `toVercelProtocolArray()` method on stream events have been removed.

`usingVercelDataProtocol()` no longer accepts a boolean:

@boostsnippet('Vercel Data Protocol', 'php')
// Before...
$agent->stream('...')->usingVercelDataProtocol(true, 'msg_1');

// After...
$agent->stream('...')->usingVercelDataProtocol('msg_1');
@endboostsnippet

Calls without arguments are unaffected. If you overrode `toVercelProtocolArray()` to render a custom event, implement the `StreamProtocol` interface and pass your protocol to `usingProtocol()` instead.

### Sub-agent activity is streamed

When a streamed run calls an `AgentTool`, the sub-agent now streams instead of running to completion behind the tool call. Its events are emitted into the parent stream, and the parent emits `ToolResult` events carrying the output produced so far. These events have `preliminary` set to `true` and are followed by the final `ToolResult` for the call.

If you count events or read tool results from a stream, skip the preliminary results:

@boostsnippet('Skipping Preliminary Results', 'php')
foreach ($agent->stream('...') as $event) {
    if ($event instanceof ToolResult && $event->preliminary) {
        continue;
    }
}
@endboostsnippet

The completed response's `text`, `reasoning`, `citations`, and `usage` now include the corresponding values from the sub-agent response. Review any cost calculation or text assertion made on a run that uses `AgentTool`.

### Streamed text is reported per step

A streamed step now emits a single `TextStart` / `TextEnd` pair. Previously, each content block emitted its own pair with a distinct message ID. `TextDelta::combine()` now separates text by step rather than by message ID, so an answer spanning several blocks is no longer split mid-sentence.

If your stream consumer opens a UI element on `TextStart` and closes it on `TextEnd`, or keys off a changing message ID, update it to expect one pair per step.

## Low-impact changes

### Paused turns expose their steps

The `pausedProviderContentBlocks()` method has been removed from `AgentResponse` and `StreamedAgentResponse`. Read the `steps` property instead:

@boostsnippet('Paused Turn State', 'php')
// Before...
$response->pausedProviderContentBlocks();

// After...
$response->steps;
@endboostsnippet

The fourth constructor argument of `Laravel\Ai\Streaming\Events\ToolApprovalRequest` is now a `Collection` of `Laravel\Ai\Responses\Data\Step` instances instead of a `$providerContentBlocks` array.

### Provider content blocks are now replay blocks

The raw provider state carried through a turn has been renamed from "provider content blocks" to "replay blocks". No changes are needed unless you construct the following objects directly or read the raw provider state from a message.

@boostsnippet('AssistantMessage Renames', 'php')
// Before...
$message->providerContentBlocks;
$message->providerContentBlocksProvider;

new AssistantMessage($content, $toolCalls, providerContentBlocks: $blocks, providerContentBlocksProvider: 'anthropic');

// After...
$message->replayBlocks;
$message->replayBlocksProvider;

new AssistantMessage($content, $toolCalls, replayBlocks: $blocks, replayBlocksProvider: 'anthropic');
@endboostsnippet

If you construct a `Laravel\Ai\Gateway\StepResponse`, rename the `providerContentBlocks:` argument to `replayBlocks:`. The constructor also accepts new `reasoning:` and `providerToolCalls:` arguments, and `toArray()` emits a `replay_blocks` key.

If you construct a `Laravel\Ai\Responses\Data\Step` or `StructuredStep`, pass the two new required arguments after `$meta`:

@boostsnippet('Step Construction', 'php')
new Step($text, $toolCalls, $toolResults, $finishReason, $usage, $meta, $reasoning, $replayBlocks);
@endboostsnippet

`Step` also accepts an optional trailing `$providerToolCalls` array, and `Step::toArray()` now emits `reasoning`, `replay_blocks`, and `provider_tool_calls` keys.

DeepSeek reasoning is now stored as a typed block rather than a raw string. For a DeepSeek turn, `AssistantMessage::$replayBlocks` is a list of `['type' => 'reasoning', 'reasoning_content' => '...']` entries.

### Reasoning events on OpenAI and xAI

OpenAI and xAI models that stream raw reasoning text rather than a summary now emit `ReasoningStart`, `ReasoningDelta`, and `ReasoningEnd` events. If your stream consumer renders reasoning, handle these events for those providers.

`$response->reasoning` moved from `AgentResponse` to `TextResponse` and is populated on non-streamed prompts as well. It contains the combined reasoning from every step, while each step's reasoning is available on `Laravel\Ai\Responses\Data\Step`.

### Protected provider hooks

Several protected methods used by custom providers and gateways have changed:

- `Providers\Concerns\GeneratesText::resolveTools()` and `throwIfNotResumable()` receive an `AgentPrompt` instead of an `Agent`
- `Providers\Concerns\GeneratesText::recordAgentFailure()` dropped its `?AgentPrompt $processedPrompt` argument, so `bool $retryable` moved from the fifth position to the fourth
- `Providers\Concerns\GeneratesText::agentCanResumeApprovals()` was removed
- `PendingResponses\Concerns\ResolvesProviderOptions::resolveProviderOptions()` and `Gateway\Concerns\PreparesStorableFiles::resolveProviderOptions()` are now `resolveProviderOptionsAndHeaders()`, returning the options and the headers as a tuple
- `Gateway\RunContext::startingStep()`, `stepCompleted()`, and `stepFailed()` accept a trailing `?string $model`, and the latter two accept a nullable `StepContext`

### The `ConversationStore` contract

No changes are needed if you use the included database store. If you bind a custom `ConversationStore`, update five method signatures.

@boostsnippet('ConversationStore Signatures', 'php')
// Receives the agent class name, so scope the lookup to the given agent...
public function latestConversationId(
    string $participantType,
    string|int $participantId,
    string $agent,
): ?string;

// Accepts the ID the conversation should be stored under, so use it when one is passed...
public function storeConversation(
    ?string $participantType,
    string|int|null $participantId,
    string $title,
    ?string $id = null,
): string;

// Receives the agent class name and a UserMessage in place of an AgentPrompt, so a message may
// be stored before a provider has been resolved. Read $message->content and $message->attachments
// in place of $prompt->prompt and $prompt->attachments...
public function storeUserMessage(
    string $conversationId,
    ?string $participantType,
    string|int|null $participantId,
    string $agent,
    UserMessage $message,
): string;

// No longer receives the participant, so look the paused turn up by conversation alone...
public function storeApprovalResults(
    string $conversationId,
    array $toolResults,
): void;
@endboostsnippet

Because a turn paused for one participant may now be resolved by another, authorize the resuming participant in your application before passing decisions back to the agent.

`storeAssistantMessage()` accepts the error a run died with as a trailing argument, so a turn that failed can be stored alongside the steps it completed. Store the turn with a `failed` status and record the message when one is passed:

@boostsnippet('storeAssistantMessage Signature', 'php')
public function storeAssistantMessage(
    string $conversationId,
    ?string $participantType,
    string|int|null $participantId,
    AgentPrompt $prompt,
    AgentResponse $response,
    ?Throwable $exception = null,
): ?string;
@endboostsnippet

### The `RemembersConversations` contract adds `continueOrStart()`

The `Laravel\Ai\Contracts\RemembersConversations` interface now includes a `continueOrStart()` method, which continues the given conversation or starts a new one when the ID is `null`:

@boostsnippet('continueOrStart', 'php')
public function continueOrStart(?string $conversationId, object $as): static;
@endboostsnippet

No changes are needed if your agents use the `Concerns\RemembersConversations` trait, which provides the method. If an agent implements the contract by hand, add the method.

### The `Agent` contract accepts more input types

`Agent::prompt()`, `stream()`, `queue()`, `broadcast()`, `broadcastNow()`, and `broadcastOnQueue()` now accept `AgentInput|UserMessage|Decisions|string` instead of `Decisions|string`, so a chat request may be handed to the agent directly:

@boostsnippet('Passing A Chat Request', 'php')
$chat = Vercel::chat($request);

$agent->withMessages($chat->history())->stream($chat);
@endboostsnippet

No changes are needed if your agents use the `Promptable` trait. If you implement `Laravel\Ai\Contracts\Agent` directly, widen the type of each `$prompt` parameter to match the contract.

### Provider and gateway signatures

The image, audio, and reranking methods on providers now accept provider options, and reranking also accepts a timeout:

@boostsnippet('Provider Signatures', 'php')
public function image(string $prompt, array $attachments = [], ?string $size = null, ?string $quality = null, ?string $model = null, ?int $timeout = null, array $providerOptions = []): ImageResponse;

public function audio(string $text, string $voice = 'default-female', ?string $instructions = null, ?string $model = null, int $timeout = 30, array $providerOptions = []): AudioResponse;

public function rerank(array $documents, string $query, ?int $limit = null, ?string $model = null, int $timeout = 30, array $providerOptions = []): RerankingResponse;
@endboostsnippet

The corresponding `ImageGateway`, `AudioGateway`, and `RerankingGateway` methods gained the applicable `$providerOptions` and `$timeout` arguments. In addition, `Laravel\Ai\Contracts\Providers\Provider` gained a `withHeaders()` method for sending custom HTTP headers. Anything extending the base `Laravel\Ai\Providers\Provider` gets `withHeaders()` for free.

Most applications are unaffected. If you have written a custom provider or gateway, update its method signatures to match.

Reranking requests now use a 30-second timeout by default. Bedrock previously used the AWS SDK default, so a long reranking call may now time out. Raise it with the new `timeout()` method:

@boostsnippet('Reranking Timeout', 'php')
Reranking::of($documents)->timeout(60)->rerank('...');
@endboostsnippet

### Stream event constructor signatures

The `$provider` argument of `Laravel\Ai\Streaming\Events\ProviderToolEvent` is now a required `string` rather than an optional `?string`. If you construct this event directly, pass the provider name.

## Getting help

If you encounter issues during the upgrade:

- Check the [upgrade guide](https://github.com/laravel/ai/blob/1.x/UPGRADE.md) for the latest details
- Visit the [GitHub discussions](https://github.com/laravel/ai/discussions) for community support
