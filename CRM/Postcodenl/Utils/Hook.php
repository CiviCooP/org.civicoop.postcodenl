<?php

/**
 * Class to implement a wrapper for invoking custom hooks
 * In civi 4.5 the argument count is increased and caused a failure.
 * This class will wrap around that issue
 */
class CRM_Postcodenl_Utils_Hook {

    public static function invoke($numParams,
                                  &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
                                  $fnSuffix
    ) {

        $version = CRM_Utils_System::version();
        list($major, $minor, $rel) = explode(".", $version);

        $hooks = CRM_Utils_Hook::singleton();
        if ($major <= 4 && $minor <= 4) {
            return $hooks->invoke($numParams,
                $arg1, $arg2, $arg3, $arg4, $arg5,
                $fnSuffix);
        }

        return $hooks->invoke($numParams,
            $arg1, $arg2, $arg3, $arg4, $arg5, $arg6,
            $fnSuffix);
    }

}