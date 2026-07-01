<?php

class Prepopulate
{
    static function prepopulate_data(mysqli $db, string $path)
    {
        $sql = file_get_contents($path);
        $sql = preg_replace('/ALTER TABLE.*?;\s*/s', '', $sql);
        $db->multi_query($sql);
        while ($db->next_result()) {;
        }
    }
}
