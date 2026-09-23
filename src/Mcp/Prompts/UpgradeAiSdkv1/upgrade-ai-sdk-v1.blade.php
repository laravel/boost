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
- Determine whether conversations are persisted, because that decides whether the backfill migration is required

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- **Back up the conversation tables** - the backfill migration rewrites every stored assistant message and drops columns
- **Resolve or abandon every turn waiting for tool approval** - a paused turn cannot be resumed once its `approval_state` data is gone

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
- `withProviderOptions(` on a Gemini agent - Gemini now uses the Interactions API, which renames the option keys
- `continueLastConversation(` - Now scoped to the current agent
- Transcript rendering or message counting - Resumed turns fold into one message, and failed turns are now stored
- `->tokens` on an `EmbeddingsResponse` - Replaced by `->usage->inputTokens`
- `new Usage(` - Text responses now use `TextUsage` with a different argument order
- `usingVercelDataProtocol(true` - The boolean first argument was removed
- `toVercelProtocolArray(` or `CanStreamUsingVercelProtocol` - Removed
- `instanceof ToolResult` in stream consumers - Sub-agent runs now emit preliminary results
- `TextStart` / `TextEnd` handling - Now one pair per step instead of per content block

**Low Priority Searches:**
- `pausedProviderContentBlocks(` - Read `$response->steps` instead
- `providerContentBlocks` - Renamed to `replayBlocks`
- `implements ConversationStore` - Five method signatures changed
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

### Conversation Messages Now Store Steps

This change affects applications that use remembered conversations. The `tool_calls` and `tool_results` columns on the `agent_conversation_messages` table have been replaced by a single `steps` column. Each assistant message now stores one entry for each model round trip, and each tool result is stored on the tool call that produced it:

@boostsnippet('Conversation Messages Now Store Steps', 'json')
[
    {"content": "", "tool_calls": [{"id": "call_1", "name": "read_file", "arguments": {}, "result": "..."}], "reasoning": "", "replay_blocks": [], "provider_tool_calls": []},
    {"content": "Done.", "tool_calls": [], "reasoning": "", "replay_blocks": [], "provider_tool_calls": []}
]
@endboostsnippet

The `approval_state` column has been replaced by a `status` column containing a `Laravel\Ai\Enums\MessageStatus` value: `completed`, `paused`, or `failed`. The reason a call is waiting for a decision is now stored on the call itself as `approval_reason`. A stored call with that key and no `result` is still pending:

@boostsnippet('Conversation Messages Now Store Steps 2', 'json')
{"id": "call_1", "name": "delete_file", "arguments": {"path": "a"}, "approval_reason": "Destructive."}
@endboostsnippet

The package's existing migration will not run again during an upgrade. If you have already migrated the conversation tables, create a new migration containing the code below. This migration adds the `steps` and `status` columns, migrates existing messages, drops the old columns, and adds the `agent` column to the `participant_index`.

Before running the migration, resolve or abandon any turns that are waiting for tool approval. Pending turns cannot be resumed after their `approval_state` data is removed.

@boostsnippet('Conversation Messages Now Store Steps 3', 'php')
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    /**
     * Run the migrations.
     */
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
                    $this->backfill($table, $conversation->conversation_id);
                }
            });

        Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
            $blueprint->longText('steps')->nullable(false)->change();
            $blueprint->dropColumn(['tool_calls', 'tool_results', 'approval_state']);
            $blueprint->dropIndex('participant_index');
            $blueprint->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
        });
    }

    /**
     * Rewrite one conversation's assistant rows as steps, each result landing on the call that made it.
     */
    protected function backfill(string $table, string $conversationId): void
    {
        $rows = $this->query($table)
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderBy('id')
            ->get();

        // A result was recorded on the row of the request that produced it, which may be a later row than its call...
        $results = $rows->flatMap(fn (object $row) => $this->decoded($row->tool_results))->keyBy('id');

        foreach ($rows as $row) {
            $meta = $this->decoded($row->meta);

            $calls = collect($this->decoded($row->tool_calls))
                ->filter(fn (array $call) => $results->has($call['id'] ?? ''))
                ->map(fn (array $call) => [
                    ...$call,
                    'result' => $results[$call['id']]['result'] ?? null,
                    ...array_filter([
                        'denied' => $results[$call['id']]['denied'] ?? false,
                        'failed' => $results[$call['id']]['failed'] ?? false,
                    ]),
                ])
                ->values()
                ->all();

            $content = (string) $row->content;

            $steps = $calls !== [] && $content !== ''
                ? [$this->step('', $calls), $this->step($content, [], $meta['reasoning'] ?? '')]
                : [$this->step($content, $calls, $meta['reasoning'] ?? '')];

            unset($meta['provider_steps'], $meta['provider_content_blocks'], $meta['reasoning']);

            $this->query($table)->where('id', $row->id)->update([
                'steps' => json_encode($steps),
                'meta' => json_encode($meta),
            ]);
        }
    }

    /**
     * Build a conversation step from its content, tool calls, and reasoning.
     *
     * @param  list<array<string, mixed>>  $calls
     * @return array<string, mixed>
     */
    protected function step(string $content, array $calls = [], string $reasoning = ''): array
    {
        return [
            'content' => $content,
            'tool_calls' => $calls,
            'reasoning' => $reasoning,
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ];
    }

    /**
     * Decode a JSON value into an array, returning an empty array for invalid or empty input.
     *
     * @return array<string, mixed>
     */
    protected function decoded(?string $json): array
    {
        return is_array($decoded = json_decode($json ?? '', true)) ? $decoded : [];
    }

    /**
     * Create a query builder for the conversation messages table.
     */
    protected function query(string $table): Builder
    {
        return DB::connection($this->getConnection())->table($table);
    }
};
@endboostsnippet

Run `{{ $assist->artisanCommand('migrate') }}` before deploying Laravel AI 1.0.

If your application reads the conversation tables directly, use `steps` instead of `tool_calls` or `tool_results`.

On `Laravel\Ai\Models\ConversationMessage`, `tool_calls`, `tool_results`, and `provider_tool_calls` are read-only. To update a message, write to `steps`. The `approval_state` attribute has been replaced by `status`, which is a `MessageStatus` value.

The following values have moved out of `meta`:

- `meta.reasoning` is now `steps[].reasoning`.
- `meta.provider_steps` and `meta.provider_content_blocks` are now `steps[].replay_blocks`.

Replay blocks are only stored while a turn is waiting for tool approval. They are removed once the turn finishes.

If your application reads stored tool calls directly, each call now contains its own result. A pending approval has an `approval_reason` but no `result`. The provider-specific `reasoning_id` and `reasoning_encrypted_content` values are no longer stored.

If your application uses `Laravel\Ai\Storage\StoredMessage` directly, replace accesses to the `$toolCalls`, `$toolResults`, and `$approvalState` properties:

@boostsnippet('Conversation Messages Now Store Steps 5', 'php')
// Before...
$message->toolCalls;
$message->toolResults;
$message->approvalState['pending'];

// After...
$message->toolCalls();
$message->toolResults();
$message->providerToolCalls();
array_filter($message->toolCalls(), fn (array $call) => PendingApproval::isPending($call));
@endboostsnippet

When creating a `StoredMessage`, pass `steps` instead of `toolCalls` and `toolResults`. Its `toArray()` method now returns `steps` and `status` instead of `tool_calls`, `tool_results`, and `approval_state`.

### Agent Middleware Wraps Each Generation Step

This change affects applications with custom agent middleware. Middleware now wraps each generation step instead of the entire agent run. A run with three generation steps therefore invokes each middleware three times.

Update each middleware `handle()` method to accept a `Laravel\Ai\PendingStep` and return the `Laravel\Ai\Gateway\StepResult` produced by `$next($step)`:

@boostsnippet('Agent Middleware Wraps Each Generation Step', 'php')
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

You can modify a step by creating a copy before passing it to the next middleware:

@boostsnippet('Agent Middleware Wraps Each Generation Step 2', 'php')
public function handle(PendingStep $step, Closure $next)
{
    if (! $step->isFirstStep()) {
        $step = $step->withoutTools('SearchDocumentation');
    }

    return $next($step);
}
@endboostsnippet

`PendingStep` provides the `withModel()`, `withInstructions()`, `withMessages()`, `withTools()`, `onlyTools()`, `withoutTools()`, `withToolChoice()`, `withMaxTokens()`, and `withProviderOptions()` methods, along with `isFirstStep()` and the `$isFinalStep` property.

Middleware may return a `StepResponse` to answer a step without calling the model. Returning any other value throws a `LogicException`.

The `AgentPrompted`, `AgentStreamed`, and `AgentFailed` events now carry the original `AgentPrompt` passed to the provider rather than a prompt modified by run middleware. If you relied on a listener receiving the modified prompt, move that logic into the middleware itself.

### Gemini Vector Store Imports Wait For Completion

This change affects applications that add files to Gemini vector stores. `addFile()` now waits for the import to finish instead of returning as soon as the import is requested:

@boostsnippet('Gemini Vector Store Imports Wait For Completion', 'php')
$store->addFile($fileId);
@endboostsnippet

The returned ID is now the document name instead of the import operation name. If your application persisted IDs returned by an earlier version, re-import those files and store the new IDs.

The call now throws a `Laravel\Ai\Exceptions\AiException` when the import fails or does not finish within five minutes. Catch this exception if your application needs to recover from an unsuccessful import.

### The AWS SDK Is No Longer Installed By Default

This change only affects applications that use the Bedrock provider. The `aws/aws-sdk-php` package is no longer installed by Laravel AI. Install it directly in your application with `{{ $assist->composerCommand('require aws/aws-sdk-php') }}`.

Resolving the Bedrock provider without the SDK installed throws a `RuntimeException`.

### Token Usage Includes All Tokens

This change affects applications that read or serialize token usage. `Usage::$promptTokens` and `Usage::$completionTokens` are now named `Usage::$inputTokens` and `Usage::$outputTokens`:

@boostsnippet('Token Usage Includes All Tokens', 'php')
// Before...
$response->usage->promptTokens;
$response->usage->completionTokens;

// After...
$response->usage->inputTokens;
$response->usage->outputTokens;
@endboostsnippet

The new properties contain the provider's complete counts. `inputTokens` includes cached and cache-written tokens, while `outputTokens` includes reasoning tokens. These categories were previously excluded from the totals.

If you previously calculated input token costs by applying a single rate to `promptTokens`, calculate each category separately: apply the base rate to `uncachedInputTokens()`, the cache-read rate to `cacheReadInputTokens`, and the cache-write rate to `cacheWriteInputTokens`. For example:

@boostsnippet('Token Usage Includes All Tokens 2', 'php')
$usage = $response->usage;

$cost = $usage->uncachedInputTokens() * $baseRate
    + ($usage->cacheReadInputTokens ?? 0) * $cacheReadRate
    + ($usage->cacheWriteInputTokens ?? 0) * $cacheWriteRate;
@endboostsnippet

The `Usage::toArray()` method and newly stored values in the `usage` column now use the `input_tokens` and `output_tokens` keys. Existing database rows retain the old keys, so reporting code that reads historical rows should support both formats.

Reported values have also changed in three places:

- Anthropic streams read the cumulative usage reported on `message_delta`, so a run using a server tool such as web search reports a higher input token count than before.
- Anthropic populates `reasoningTokens` from the thinking token breakdown rather than always reporting `0`.
- Cohere embeddings on Bedrock report the input token count returned by the API rather than always reporting `0`.

## Medium-impact changes

### Resumed Turns Fold Into The Message They Paused On

This change affects applications that inspect messages in conversations using tool approval. Resuming a paused turn now appends the resumed steps to the assistant message where the pause occurred instead of storing a second assistant message. The turn's usage is summed, its citations are merged, and `storeAssistantMessage()` returns the ID of the original message.

Update transcript rendering and message-counting logic to expect one assistant message per turn, including turns that paused for approval.

### Failed Turns Are Recorded

This change affects applications that render or count messages in remembered conversations. A remembered run that throws now stores its completed steps as an assistant message with a `failed` status. The error message is stored in `meta.error`. Previously, the failed turn was not stored.

The turn is recorded once the run is out of providers to fail over to, so a run that fails over and then succeeds stores only the successful turn. A run that died before its first step stores nothing, unless it was resuming a paused turn, which is failed in place.

If failed turns should not appear in your application, filter them by status:

@boostsnippet('Failed Turns Are Recorded', 'php')
$conversation->messages()->where('status', MessageStatus::Completed);
@endboostsnippet

Streamed runs report their failure through a new `catch()` callback on `StreamableAgentResponse`, which receives the exception before it is rethrown.

### Latest Conversations Are Scoped To The Agent

This change affects applications where a participant uses more than one agent that remembers conversations. `continueLastConversation()` now resolves the participant's latest conversation with the current agent instead of the latest conversation with any agent:

@boostsnippet('Latest Conversations Are Scoped To The Agent', 'php')
// Before... the user's newest conversation, whichever agent wrote it.
// After... the user's newest conversation with this agent.
(new SupportAgent)->continueLastConversation($user)->prompt('...');
@endboostsnippet

The conversation migration above updates the required index. If your application relied on the previous cross-agent behavior, resolve the conversation ID explicitly and pass it to `continue()`.

### Gemini Uses The Interactions API

This change affects applications that pass raw provider options to Gemini. Gemini text generation, streaming, tools, structured output, image generation, speech, and transcription now use the Interactions API. The base URL is unchanged, and embeddings, files, and vector stores continue to use their existing endpoints.

Raw provider options are passed to Gemini as given, so any options you send must use the names expected by the Interactions API:

@boostsnippet('Gemini Uses The Interactions API', 'php')
// Before...
$agent->withProviderOptions(['thinkingConfig' => ['thinkingBudget' => 1024]]);

// After...
$agent->withProviderOptions(['thinking_level' => 'high']);
@endboostsnippet

- `thinkingConfig` is now `thinking_level` and `thinking_summaries`.
- `toolConfig` is now `tool_choice`, inside the generation config.
- `cachedContent` no longer exists.
- `safetySettings`, `serviceTier`, and `store` are still sent beside the generation config, under their snake case names.

Refer to Gemini's [Interactions API migration guide](https://ai.google.dev/gemini-api/docs/migrate-to-interactions) for other renamed fields.

### Text Responses Include A `TextUsage` Object

This change affects applications that construct response or stream objects directly. The `Laravel\Ai\Responses\Data\Usage` class now contains only `inputTokens` and `outputTokens`. Text-specific properties and methods have moved to `Laravel\Ai\Responses\Data\TextUsage`:

- `cacheReadInputTokens`
- `cacheWriteInputTokens`
- `reasoningTokens`
- `add()`
- `uncachedInputTokens()`

Text, agent, step, and stream responses now contain a `TextUsage` instance. `StreamEnd::combineUsage()` also returns `TextUsage`.

No changes are required if your application only reads usage from a response. If you construct a `TextResponse`, `StepResponse`, `Step`, or `StreamEnd`, pass a `TextUsage` instance using the new argument order:

@boostsnippet('Text Responses Include A TextUsage Object', 'php')
// Before...
use Laravel\Ai\Responses\Data\Usage;

new Usage($promptTokens, $completionTokens, $cacheWriteInputTokens, $cacheReadInputTokens, $reasoningTokens);

// After...
use Laravel\Ai\Responses\Data\TextUsage;

new TextUsage($inputTokens, $outputTokens, $cacheReadInputTokens, $cacheWriteInputTokens, $reasoningTokens);
@endboostsnippet

The cache read and cache write arguments have swapped positions. The three optional counts are now nullable and contain `null` when the provider does not report them. Use the null coalescing operator when treating these values as numbers.

### Usage Is Reported For Every Response

This change affects applications that read `EmbeddingsResponse::$tokens` or construct response objects directly. The `$tokens` property has been replaced with a `$usage` object:

@boostsnippet('Usage Is Reported For Every Response', 'php')
// Before...
$response->tokens;

// After...
$response->usage->inputTokens;
@endboostsnippet

`EmbeddingsResponse::toArray()` and `jsonSerialize()` now emit a `usage` object instead of a `tokens` integer. Update code that consumes the serialized response.

`AudioResponse` and `RerankingResponse` now carry a `$usage` property as well. Each capability reports its relevant billing metrics through its usage class:

- `ImageResponse::$usage` is an `ImageUsage`, adding `imageInputTokens` and `imageOutputTokens`.
- `TranscriptionResponse::$usage` is a `TranscriptionUsage`, adding `audioSeconds`.
- `RerankingResponse::$usage` is a `RerankingUsage`, adding `searchUnits`.
- `AudioResponse::$usage` and `EmbeddingsResponse::$usage` are plain `Usage` instances.

The added counts are `null` when the provider does not report them.

When constructing these responses, pass the usage object before `Meta`. It is the second argument for `EmbeddingsResponse`, `AudioResponse`, `RerankingResponse`, and `ImageResponse`, and the third argument for `TranscriptionResponse`, after the text and segments.

### Stream Protocols

This change affects applications that pass a boolean to `usingVercelDataProtocol()` or implement custom stream event serialization. Stream protocols are now objects implementing `Laravel\Ai\Streaming\Protocols\StreamProtocol`. The `Laravel\Ai\Responses\Concerns\CanStreamUsingVercelProtocol` trait and stream event `toVercelProtocolArray()` methods have been removed.

`usingVercelDataProtocol()` no longer accepts a boolean. Remove the first argument from any call that passes one:

@boostsnippet('Stream Protocols', 'php')
// Before...
$agent->stream('...')->usingVercelDataProtocol(true, 'msg_1');

// After...
$agent->stream('...')->usingVercelDataProtocol('msg_1');
@endboostsnippet

Calls without arguments require no changes. If your application overrode `toVercelProtocolArray()` to render a custom event, implement `StreamProtocol` and pass the protocol to `usingProtocol()`.

### Sub-Agent Activity Is Streamed

This change affects applications that consume streams containing an `AgentTool`. Sub-agent events are now emitted into the parent stream. While the sub-agent runs, the parent emits `ToolResult` events with `preliminary` set to `true`, followed by the final `ToolResult`.

If you count events or read tool results from a stream, skip the preliminary results:

@boostsnippet('SubAgent Activity Is Streamed', 'php')
foreach ($agent->stream('...') as $event) {
    if ($event instanceof ToolResult && $event->preliminary) {
        continue;
    }
}
@endboostsnippet

The completed response's `text`, `reasoning`, `citations`, and `usage` now include the corresponding values from the sub-agent response. Review any cost calculation or text assertion made on a run that uses `AgentTool`.

### Streamed Text Is Reported Per Step

This change affects stream consumers that track `TextStart` and `TextEnd` events or associate content with a message ID. Each streamed generation step now emits one `TextStart` / `TextEnd` pair. Previously, each content block emitted its own pair with a distinct message ID.

Update stream consumers to expect one pair per generation step. `TextDelta::combine()` now separates text by step instead of message ID.

## Low-impact changes

### Paused Turns Expose Their Steps

This change only affects applications that inspect paused provider state directly. The `pausedProviderContentBlocks()` method has been removed from `AgentResponse` and `StreamedAgentResponse`. Read the `steps` property instead:

@boostsnippet('Paused Turns Expose Their Steps', 'php')
// Before...
$response->pausedProviderContentBlocks();

// After...
$response->steps;
@endboostsnippet

If your application constructs `Laravel\Ai\Streaming\Events\ToolApprovalRequest` directly, pass a collection of `Laravel\Ai\Responses\Data\Step` instances as the fourth argument instead of a provider content blocks array.

### Provider Content Blocks Are Now Replay Blocks

This change only affects applications that construct response data objects directly or read raw provider state from a message. The raw provider state carried through a turn is now called "replay blocks" instead of "provider content blocks".

If you read or construct an `AssistantMessage`, rename the properties and constructor arguments:

@boostsnippet('Provider Content Blocks Are Now Replay Blocks', 'php')
// Before...
$message->providerContentBlocks;
$message->providerContentBlocksProvider;

new AssistantMessage($content, $toolCalls, providerContentBlocks: $blocks, providerContentBlocksProvider: 'anthropic');

// After...
$message->replayBlocks;
$message->replayBlocksProvider;

new AssistantMessage($content, $toolCalls, replayBlocks: $blocks, replayBlocksProvider: 'anthropic');
@endboostsnippet

When constructing a `Laravel\Ai\Gateway\StepResponse`, rename the `providerContentBlocks:` argument to `replayBlocks:`. The constructor also accepts `reasoning:` and `providerToolCalls:` arguments, and `toArray()` now emits a `replay_blocks` key.

If you construct a `Laravel\Ai\Responses\Data\Step` or `StructuredStep`, pass the two new required arguments after `$meta`:

@boostsnippet('Provider Content Blocks Are Now Replay Blocks 2', 'php')
new Step($text, $toolCalls, $toolResults, $finishReason, $usage, $meta, $reasoning, $replayBlocks);
@endboostsnippet

`Step` also accepts an optional trailing `$providerToolCalls` array, and `Step::toArray()` now emits `reasoning`, `replay_blocks`, and `provider_tool_calls` keys.

DeepSeek reasoning is now stored as a typed replay block instead of a raw string. If your application reads this value directly, expect `AssistantMessage::$replayBlocks` to contain `['type' => 'reasoning', 'reasoning_content' => '...']` entries.

### Reasoning Events On OpenAI And xAI

This change affects stream consumers that render reasoning from OpenAI or xAI. Models that stream raw reasoning text instead of a summary now emit `ReasoningStart`, `ReasoningDelta`, and `ReasoningEnd` events. Update your stream consumer to handle these events.

`$response->reasoning` moved from `AgentResponse` to `TextResponse` and is populated on non-streamed prompts as well. It contains the combined reasoning from every step, while each step's reasoning is available on `Laravel\Ai\Responses\Data\Step`.

### Protected Provider Hooks

This change only affects custom providers and gateways that override Laravel AI's protected hooks. Update the following method names and signatures:

- `Providers\Concerns\GeneratesText::resolveTools()` and `throwIfNotResumable()` receive an `AgentPrompt` instead of an `Agent`.
- `Providers\Concerns\GeneratesText::recordAgentFailure()` no longer accepts its `?AgentPrompt $processedPrompt` argument, so `bool $retryable` moved from the fifth position to the fourth.
- `Providers\Concerns\GeneratesText::agentCanResumeApprovals()` was removed.
- `PendingResponses\Concerns\ResolvesProviderOptions::resolveProviderOptions()` and `Gateway\Concerns\PreparesStorableFiles::resolveProviderOptions()` are now `resolveProviderOptionsAndHeaders()`, returning the options and the headers as a tuple.
- `Gateway\RunContext::startingStep()`, `stepCompleted()`, and `stepFailed()` accept a trailing `?string $model`, and the latter two accept a nullable `StepContext`.

### The `ConversationStore` Contract

This change only affects applications that bind a custom `ConversationStore`; no changes are required when using the included database store. Custom stores should update the following method signatures.

`latestConversationId()` receives the agent class name. Scope the lookup to the given agent:

@boostsnippet('The ConversationStore Contract', 'php')
public function latestConversationId(
    string $participantType,
    string|int $participantId,
    string $agent,
): ?string;
@endboostsnippet

`storeConversation()` accepts the ID the conversation should be stored under. Use the given ID when one is passed:

@boostsnippet('The ConversationStore Contract 2', 'php')
public function storeConversation(
    ?string $participantType,
    string|int|null $participantId,
    string $title,
    ?string $id = null,
): string;
@endboostsnippet

`storeUserMessage()` receives the agent class name and a `UserMessage` instead of an `AgentPrompt`, so a message can be stored before a provider has been resolved. Read `$message->content` and `$message->attachments` instead of `$prompt->prompt` and `$prompt->attachments`:

@boostsnippet('The ConversationStore Contract 3', 'php')
public function storeUserMessage(
    string $conversationId,
    ?string $participantType,
    string|int|null $participantId,
    string $agent,
    UserMessage $message,
): string;
@endboostsnippet

`storeApprovalResults()` no longer receives the participant. Look up the paused turn by conversation ID. Since the package no longer scopes this lookup to a participant, authorize the participant who is resuming the run in your application before passing decisions to the agent:

@boostsnippet('The ConversationStore Contract 4', 'php')
public function storeApprovalResults(
    string $conversationId,
    array $toolResults,
): void;
@endboostsnippet

`storeAssistantMessage()` accepts the exception that caused a run to fail as a trailing argument, so a failed turn can be stored alongside the steps it completed. Store the turn with a `failed` status and record the exception message when one is passed:

@boostsnippet('The ConversationStore Contract 5', 'php')
public function storeAssistantMessage(
    string $conversationId,
    ?string $participantType,
    string|int|null $participantId,
    AgentPrompt $prompt,
    AgentResponse $response,
    ?Throwable $exception = null,
): ?string;
@endboostsnippet

### The `RemembersConversations` Contract Adds `continueOrStart()`

This change only affects agents that implement `Laravel\Ai\Contracts\RemembersConversations` directly. The interface now includes a `continueOrStart()` method, which continues the given conversation or starts a new one when its ID is not `null`, or starts a new conversation when the ID is `null`:

@boostsnippet('The RemembersConversations Contract Adds continueOrStart', 'php')
public function continueOrStart(?string $conversationId, object $as): static;
@endboostsnippet

No changes are required for agents that use the `Concerns\RemembersConversations` trait. Otherwise, add the method to your implementation.

### The `Agent` Contract Accepts More Input Types

This change only affects classes that implement `Laravel\Ai\Contracts\Agent` directly. The `prompt()`, `stream()`, `queue()`, `broadcast()`, `broadcastNow()`, and `broadcastOnQueue()` methods now accept `AgentInput|UserMessage|Decisions|string` instead of `Decisions|string`:

@boostsnippet('The Agent Contract Accepts More Input Types', 'php')
$chat = Vercel::chat($request);

$agent->withMessages($chat->history())->stream($chat);
@endboostsnippet

No changes are required for agents that use the `Promptable` trait. Otherwise, widen each `$prompt` parameter type to match the contract.

### Provider And Gateway Signatures

This change only affects custom providers and gateways. Image, audio, and reranking methods now accept provider options, and reranking methods also accept a timeout:

@boostsnippet('Provider And Gateway Signatures', 'php')
public function image(string $prompt, array $attachments = [], ?string $size = null, ?string $quality = null, ?string $model = null, ?int $timeout = null, array $providerOptions = []): ImageResponse;

public function audio(string $text, string $voice = 'default-female', ?string $instructions = null, ?string $model = null, int $timeout = 30, array $providerOptions = []): AudioResponse;

public function rerank(array $documents, string $query, ?int $limit = null, ?string $model = null, int $timeout = 30, array $providerOptions = []): RerankingResponse;
@endboostsnippet

Update the corresponding `ImageGateway`, `AudioGateway`, and `RerankingGateway` method signatures with the applicable `$providerOptions` and `$timeout` arguments. The `Laravel\Ai\Contracts\Providers\Provider` contract also includes a `withHeaders()` method. Providers extending `Laravel\Ai\Providers\Provider` inherit this method and require no additional changes.

Reranking requests now use a 30-second timeout by default. Bedrock previously used the AWS SDK default, so a long reranking call may now time out. Raise it with the new `timeout()` method:

@boostsnippet('Provider And Gateway Signatures 2', 'php')
Reranking::of($documents)->timeout(60)->rerank('...');
@endboostsnippet

### Stream Event Constructor Signatures

This change only affects applications that construct `Laravel\Ai\Streaming\Events\ProviderToolEvent` directly. The event's `$provider` argument is now a required `string`; pass the provider name when constructing the event.

## Getting help

If you encounter issues during the upgrade:

- Check the [upgrade guide](https://github.com/laravel/ai/blob/1.x/UPGRADE.md) for the latest details
- Visit the [GitHub discussions](https://github.com/laravel/ai/discussions) for community support
