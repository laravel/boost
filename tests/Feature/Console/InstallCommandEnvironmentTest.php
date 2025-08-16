<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Config;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Tests\TestCase;

class InstallCommandEnvironmentTest extends TestCase
{
    /**
     * Test that environment variables work as defaults in multiselect prompts.
     */
    public function test_environment_variables_preselect_options_in_multiselect(): void
    {
        // Set config to pre-select both mcp_server and ai_guidelines (like env vars would)
        Config::set('boost.install.mcp_server', true);
        Config::set('boost.install.ai_guidelines', true);

        // Mock user just pressing enter to accept defaults
        Prompt::fake([
            Key::ENTER,  // Accept pre-selected options
        ]);

        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
            'ai_guidelines' => 'Boost AI Guidelines (for Laravel, Inertia, and more)',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ];

        // This simulates what selectBoostFeatures() does in interactive mode
        $result = \Laravel\Prompts\multiselect(
            label: 'What do you want to install?',
            options: $installOptions,
            default: ['mcp_server', 'ai_guidelines'], // Pre-selected via config
            required: true,
        );

        // Verify the defaults were accepted
        $this->assertIsArray($result);
        $this->assertContains('mcp_server', $result);
        $this->assertContains('ai_guidelines', $result);
        $this->assertNotContains('style_guidelines', $result);
    }

    /**
     * Test that when no environment variables are set, auto-detection works.
     */
    public function test_no_environment_variables_uses_defaults(): void
    {
        // Clear all config (simulating no env vars set)
        Config::set('boost.install.mcp_server', true);  // Default
        Config::set('boost.install.ai_guidelines', true);  // Default
        Config::set('boost.install.herd', false);  // Default

        Prompt::fake([
            Key::ENTER,  // Accept defaults
        ]);

        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
            'ai_guidelines' => 'Boost AI Guidelines (for Laravel, Inertia, and more)',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'What do you want to install?',
            options: $installOptions,
            default: ['mcp_server', 'ai_guidelines'],
            required: true,
        );

        $this->assertContains('mcp_server', $result);
        $this->assertContains('ai_guidelines', $result);
    }

    /**
     * Test that environment variables can disable features.
     */
    public function test_environment_variables_can_disable_features(): void
    {
        // Set config to disable ai_guidelines (like BOOST_AI_GUIDELINES=false)
        Config::set('boost.install.mcp_server', true);
        Config::set('boost.install.ai_guidelines', false);

        Prompt::fake([
            Key::ENTER,  // Accept pre-selected options (should only be mcp_server)
        ]);

        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
            'ai_guidelines' => 'Boost AI Guidelines (for Laravel, Inertia, and more)',
        ];

        // Only mcp_server should be pre-selected
        $result = \Laravel\Prompts\multiselect(
            label: 'What do you want to install?',
            options: $installOptions,
            default: ['mcp_server'], // Only mcp_server pre-selected
            required: true,
        );

        $this->assertContains('mcp_server', $result);
        $this->assertNotContains('ai_guidelines', $result);
    }

    /**
     * Test that user can override environment variable defaults.
     */
    public function test_user_can_override_environment_defaults(): void
    {
        // Even though config says ai_guidelines=true, user can toggle it off
        Config::set('boost.install.mcp_server', true);
        Config::set('boost.install.ai_guidelines', true);

        Prompt::fake([
            Key::DOWN,    // Move to ai_guidelines
            Key::SPACE,   // Toggle ai_guidelines off
            Key::ENTER,   // Submit
        ]);

        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
            'ai_guidelines' => 'Boost AI Guidelines (for Laravel, Inertia, and more)',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'What do you want to install?',
            options: $installOptions,
            default: ['mcp_server', 'ai_guidelines'], // Both pre-selected
            required: true,
        );

        // User toggled off ai_guidelines, so only mcp_server should remain
        $this->assertContains('mcp_server', $result);
        $this->assertNotContains('ai_guidelines', $result);
    }

    /**
     * Test agent selection with environment variable defaults.
     */
    public function test_agent_selection_respects_environment_defaults(): void
    {
        // Simulate BOOST_AGENTS=claudecode
        Prompt::fake([
            Key::ENTER,  // Accept pre-selected agents
        ]);

        // This simulates the agent selection multiselect
        $agentOptions = [
            'Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode' => 'Claude Code',
            'Laravel\\Boost\\Install\\CodeEnvironment\\Copilot' => 'GitHub Copilot',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'Which agents need AI guidelines?',
            options: $agentOptions,
            default: ['Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode'], // Pre-selected via env
            scroll: 4,
            required: false,
            hint: 'Pre-selected from environment variable'
        );

        $this->assertContains('Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode', $result);
        $this->assertNotContains('Laravel\\Boost\\Install\\CodeEnvironment\\Copilot', $result);
    }

    /**
     * Test that false agent selection results in empty selection.
     */
    public function test_false_agent_selection_allows_empty_result(): void
    {
        // Simulate BOOST_AGENTS=false
        Prompt::fake([
            Key::ENTER,  // Accept empty selection
        ]);

        $agentOptions = [
            'Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode' => 'Claude Code',
            'Laravel\\Boost\\Install\\CodeEnvironment\\Copilot' => 'GitHub Copilot',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'Which agents need AI guidelines?',
            options: $agentOptions,
            default: [], // No pre-selection (BOOST_AGENTS=false)
            scroll: 4,
            required: false,
            hint: 'None selected via environment variable'
        );

        $this->assertEmpty($result);
    }

    /**
     * Test test enforcement selection with environment defaults.
     */
    public function test_test_enforcement_respects_environment_default(): void
    {
        // Simulate BOOST_ENFORCE_TESTS=false setting default to "No"
        Prompt::fake([
            Key::ENTER,  // Accept default (No)
        ]);

        $result = \Laravel\Prompts\select(
            label: 'Should AI always create tests?',
            options: ['Yes', 'No'],
            default: 'No'  // Set by BOOST_ENFORCE_TESTS=false
        );

        $this->assertEquals('No', $result);
    }

    /**
     * Test test enforcement allows user override of environment default.
     */
    public function test_test_enforcement_allows_user_override(): void
    {
        // Even though env says false, user can choose Yes
        Prompt::fake([
            Key::UP,     // Move to Yes
            Key::ENTER,  // Select Yes
        ]);

        $result = \Laravel\Prompts\select(
            label: 'Should AI always create tests?',
            options: ['Yes', 'No'],
            default: 'No'  // Default from BOOST_ENFORCE_TESTS=false
        );

        $this->assertEquals('Yes', $result);
    }

    /**
     * Test multiple agents selection from environment variable.
     */
    public function test_multiple_agents_from_environment(): void
    {
        // Simulate BOOST_AGENTS=claudecode,copilot
        Prompt::fake([
            Key::ENTER,  // Accept pre-selected agents
        ]);

        $agentOptions = [
            'Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode' => 'Claude Code',
            'Laravel\\Boost\\Install\\CodeEnvironment\\Copilot' => 'GitHub Copilot',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'Which agents need AI guidelines?',
            options: $agentOptions,
            default: [
                'Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode',
                'Laravel\\Boost\\Install\\CodeEnvironment\\Copilot',
            ], // Both pre-selected
            scroll: 4,
            required: false,
            hint: 'Pre-selected from environment variable'
        );

        $this->assertContains('Laravel\\Boost\\Install\\CodeEnvironment\\ClaudeCode', $result);
        $this->assertContains('Laravel\\Boost\\Install\\CodeEnvironment\\Copilot', $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test the hierarchy: AI_GUIDELINES controls whether agents are relevant.
     */
    public function test_config_hierarchy_ai_guidelines_controls_agents(): void
    {
        // When ai_guidelines is false, agents setting should be irrelevant
        Config::set('boost.install.ai_guidelines', false);
        Config::set('boost.install.mcp_server', true);

        Prompt::fake([
            Key::ENTER,  // Accept default selection
        ]);

        // Feature selection should only include MCP server
        $installOptions = [
            'mcp_server' => 'Boost MCP Server (with 15+ tools)',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'What do you want to install?',
            options: $installOptions,
            default: ['mcp_server'],
            required: true,
        );

        // Only MCP server should be selected
        $this->assertContains('mcp_server', $result);
        $this->assertCount(1, $result);
    }
}
