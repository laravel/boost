<?php

declare(strict_types=1);

namespace Laravel\Boost\Install;

use Illuminate\Support\Collection;
use Laravel\Boost\Contracts\SupportsSkills;

class GuidelineSkillAdapter
{
    protected const SKILL_NAME = 'laravel-boost-guidelines';

    public function __construct(
        protected SupportsSkills $agent,
    ) {}

    /**
     * @param  Collection<string, array>  $guidelines
     */
    public function sync(Collection $guidelines): bool
    {
        $composed = GuidelineComposer::composeGuidelines($guidelines);

        if (empty($composed)) {
            return false;
        }

        return $this->writeSkill($composed);
    }

    public function skillName(): string
    {
        return self::SKILL_NAME;
    }

    protected function writeSkill(string $content): bool
    {
        $targetDir = base_path($this->agent->skillsPath().DIRECTORY_SEPARATOR.self::SKILL_NAME);

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0755, true)) {
            return false;
        }

        $skillContent = "---\nname: ".self::SKILL_NAME."\ndescription: \"Laravel Boost project guidelines — conventions, tools, and rules curated by Laravel maintainers. Always apply.\"\n---\n\n".$content;

        return file_put_contents($targetDir.DIRECTORY_SEPARATOR.'SKILL.md', $skillContent) !== false;
    }
}
