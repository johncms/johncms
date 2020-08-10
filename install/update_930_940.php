<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();

$schema->table(
    'forum_sections',
    static function (Blueprint $table) {
        $table->text('meta_description')->nullable()->after('description');
        $table->string('meta_keywords')->nullable()->after('meta_description');
    }
);

$schema->table(
    'forum_topic',
    static function (Blueprint $table) {
        $table->text('meta_description')->nullable()->after('description');
        $table->string('meta_keywords')->nullable()->after('meta_description');
    }
);

echo 'Update complete!';
