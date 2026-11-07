<?php

/**
 * A file field always holds registered files, has no default value and no text length: their
 * remains leave the settings of the fields, and the references of the defaults leave the links.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $obsolete = array_flip(['file_storage', 'default', 'min_length', 'max_length']);
        foreach ($this->db->select("SELECT id, settings FROM collection_fields WHERE type = 'file' AND settings IS NOT NULL") as $field) {
            $settings = json_decode((string) $field['settings'], true);
            if (! is_array($settings)) {
                continue;
            }
            $kept = array_diff_key($settings, $obsolete);
            if ($kept !== $settings) {
                $this->db->execute('UPDATE collection_fields SET settings = ? WHERE id = ?', [
                    json_encode($kept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    $field['id'],
                ]);
            }
        }
        $this->db->execute('DELETE FROM collection_file_value_links WHERE item_id IS NULL');
    }
};
