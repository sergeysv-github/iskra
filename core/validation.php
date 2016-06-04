<?php

namespace core;

class validation
{
    /**
     * Returns a PHP callback for this particular rule.
     * 
     * @param string $validator Validator to be used.
     * @return string PHP callback pointing to the actual validator.
     */
    public static function get_validator($validator)
    {
        /*
         * If we pass a forward slash-separated rule here, it means that
         * there's a class with a static method which would perform the
         * validation.
         * 
         * If not, then one of the built-in validators should be used.
         */
        if (strpos($validator, '/') !== FALSE) {
            list ($classname, $method) = explode('/', $validator);
            if (strpos($classname, '\\') === FALSE) {
                // If namespace not specified, \models is used by default.
                $classname = '\\models\\'.$classname;
            }
            return $classname.'::validate_'.$method;
        } else {
            return __CLASS__.'::validate_'.$validator;
        }
    }
    
    public static function validate_email($value)
    {
        if (!preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $value)) {
            return [FALSE, _('Email address is invalid or empty.')];
        }
        return TRUE;
    }
    
    public static function validate_version_number($value)
    {
        if (!preg_match('/^[0-9]{1}[0-9\.\+]*$/', $value)) {
            return [FALSE, _('Invalid version number. Only dots and numbers are accepted.')];
        }
        return TRUE;
    }
    
    public static function validate_number_positive($value)
    {
        $value = intval($value);
        if ($value <= 0) {
            return [FALSE, _('Must be more than zero.')];
        }
        return TRUE;
    }
    
    public static function validate_number_positive_as_string($value)
    {
        $value = intval($value);
        if ($value <= 0) {
            return [FALSE, _('Must be specified.')];
        }
        return TRUE;
    }
    
    public static function validate_number($value)
    {
        if (!preg_match('/^-?[0-9]+$/', $value)) {
            return [FALSE, _('Must be a number.')];
        }
        return TRUE;
    }
    
    public static function validate_alpha($value)
    {
        if (!preg_match('/^[[:alpha:]]+$/', $value)) {
            return [FALSE, _('Must only contain alphabetic characters.')];
        }
        return TRUE;
    }
    
    public static function validate_id($value)
    {
        return self::validate_number_positive($value);
    }
    
    public static function validate_string_not_empty($value)
    {
        if (trim(strval($value)) === '') {
            return [FALSE, _('Must not be empty.')];
        }
        return TRUE;
    }
    
    public static function validate_date_au($value)
    {
        if (empty($value)) {
            return TRUE;
        }
        if (!preg_match('/^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[1,3-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/', $value)) {
            return [FALSE, _('The format must be dd/mm/YYYY.')];
        }
        return TRUE;
    }
}
