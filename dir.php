<?php

// Apply a function to every entry in a directory
// this is *old* code, note that there are much better ways to do this nowadays
// and that you should probably modernize this or replace it entirely. The only
// reason this file is here is to make this repo self contained and 'ready to run'

// Especially the use of 'eval' is a hack and has been superceded by call_user_func
// for purposes like these in more modern php code.

function dir_apply($dir,$callback,$path = false, $hidden = false,$maxcall = 0,$params=null,$recurse=false)

{
        $n = 0;

        if (!is_dir($dir)) {
                return 0;
        }

        if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                        // if we're processing hidden files or the file is not a hidden file
                        // we do the call back
                        if ($hidden || substr($file,0,1) != '.') {

                                // prefix with the full name of the path if the caller wants it so

                                if ($recurse && is_dir($dir . "/" . $file)) {
                                        if ($file != '.' && $file != '..' && !is_link($dir . "/" . $file)) {
                                                dir_apply($dir . "/" . $file,$callback,$path,$hidden,$maxcall,$params,$recurse);
                                        }
                                }
                                else {

                                        if ($path) {
                                                $file = $dir . "/" . $file;
                                        }

                                        if ($callback) {
                                                if ($params) {
                                                        eval("$callback(\$" . "file,\$" . "params);");
                                                }
                                                else {
                                                        eval("$callback(\$" . "file);");
                                                }
                                        }
                                }

                                $n++;

                                if ($maxcall && $n >= $maxcall) {
                                        break;
                                }
                        }
                }

                closedir($dh);
        }

        return $n;
}
