<?php
/**
 * Pterodactyl Rule helper
 *
 * @package blesta
 * @subpackage blesta.components.modules.Pterodactyl.lib
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylRule
{
    /**
     * Parses an egg variable from Pterodactyl and returns a Blesta input validation rule array
     *
     * @param string $eggVariable The egg variabe from Pteerodactyl to create rules for and from
     * @return array A list of Blesta rules parsed from the Pterodactyl rule string
     */
    public function parseEggVariable($eggVariable)
    {
        $rules = [];
        $ruleStrings = explode('|', $eggVariable->attributes->rules);
        $fieldName = $eggVariable->attributes->name;
        foreach ($ruleStrings as $ruleString) {
            $ruleParts = explode(':', $ruleString);
            $ruleName = $ruleParts[0];

            $ruleParameters = [];
            if (isset($ruleParts[1])) {
                $ruleParameters = explode(',', $ruleParts[1]);
            }

            if (method_exists($this, $ruleName)) {
                $rules[$ruleName] = call_user_func_array(
                    [$this, $ruleName],
                    [$fieldName, $ruleParameters]
                );
            }
        }

        return $rules;
    }

    /**
     * Gets a rule to require the given field
     *
     * @param string $fieldName The name of the required field
     * @return array An array representing the
     */
    private function required($fieldName)
    {
        return [
            'rule' => 'isEmpty',
            'negate' => true,
            'message' => Language::_('PterodactylRule.!error.required', true, $fieldName)
        ];
    }

    /**
     * Gets a rule to require the given field
     *
     * @param string $fieldName The name of the required field
     * @param array $ruleParameters A list parameters for the validation rule
     * @return array An array representing the
     */
    private function regex($fieldName, array $ruleParameters)
    {
        return [
            'rule' => ['matches', $ruleParameters[0]],
            'message' => Language::_('PterodactylRule.!error.regex', true, $fieldName, $ruleParameters[0])
        ];
    }
}
