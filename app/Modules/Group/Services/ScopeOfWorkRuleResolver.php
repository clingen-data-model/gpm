<?php

namespace App\Modules\Group\Services;

use InvalidArgumentException;

class ScopeOfWorkRuleResolver
{
    public function resolve(string $ruleKey, string $groupType): array
    {
        $rules = config('scope_of_work.rules', []);

        $rule = $rules[$ruleKey] ?? null;

        if (!$rule) {
            throw new InvalidArgumentException("Scope of Work rule [{$ruleKey}] is not defined.");
        }

        $groups = $rule['groups'] ?? [];

        if ($this->hasGroupSpecificRules($groups)) {
            if (!isset($groups[$groupType])) {
                throw new InvalidArgumentException("Scope of Work rule [{$ruleKey}] does not support group type [{$groupType}].");
            }

            return $this->normalizeRuleResult(array_merge(
                collect($rule)->except('groups')->all(),
                $groups[$groupType],
                [
                    'rule_key' => $ruleKey,
                    'group_type' => $groupType,
                ]
            ));
        }

        if (!in_array($groupType, $groups, true)) {
            throw new InvalidArgumentException("Scope of Work rule [{$ruleKey}] does not support group type [{$groupType}].");
        }

        return $this->normalizeRuleResult(array_merge($rule, [
            'rule_key' => $ruleKey,
            'group_type' => $groupType,
        ]));
    }

    private function hasGroupSpecificRules(array $groups): bool
    {
        return collect($groups)->keys()->contains(fn ($key) => is_string($key));
    }

    private function normalizeRuleResult(array $rule): array
    {
        $requiresApproval = $rule['requires_approval'] ?? null;

        if ($requiresApproval === true) {
            $rule['requires_approval'] = 'yes';
        }

        if ($requiresApproval === false) {
            $rule['requires_approval'] = 'no';
        }

        return $rule;
    }
}