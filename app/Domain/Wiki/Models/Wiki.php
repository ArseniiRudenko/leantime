<?php

namespace Leantime\Domain\Wiki\Models;

class Wiki
{
    public int $id;

    public string $title;

    public int $author;

    public string $created;

    public int $projectId;

    public string $category;

    public function __construct() {}
}
