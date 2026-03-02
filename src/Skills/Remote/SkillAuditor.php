<?php

declare(strict_types=1);

namespace Laravel\Boost\Skills\Remote;

use Illuminate\Support\Facades\Http;
use Throwable;

class SkillAuditor
{
    protected string $auditUrl = 'https://add-skill.vercel.sh/audit';

    protected int $timeoutSeconds = 3;

    /**
     * @param  array<int, string>  $skillSlugs
     * @return array<string, array<int, AuditResult>>
     */
    public function audit(string $source, array $skillSlugs): array
    {
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->get($this->auditUrl, [
                    'source' => $source,
                    'skills' => implode(',', $skillSlugs),
                ]);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            /** @var array<string, mixed> $data */
            return $this->parseResponse($data);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, AuditResult>>
     */
    protected function parseResponse(array $data): array
    {
        $results = [];

        foreach ($data as $skill => $partners) {
            if (! is_array($partners)) {
                continue;
            }

            $skillResults = [];

            foreach ($partners as $partner => $audit) {
                if (! is_array($audit) || ! isset($audit['risk'])) {
                    continue;
                }

                $skillResults[] = new AuditResult(
                    partner: ucfirst((string) $partner),
                    risk: (string) $audit['risk'],
                    alerts: isset($audit['alerts']) ? (int) $audit['alerts'] : null,
                    analyzedAt: isset($audit['analyzedAt']) ? (string) $audit['analyzedAt'] : null,
                );
            }

            if ($skillResults !== []) {
                $results[$skill] = $skillResults;
            }
        }

        return $results;
    }
}
