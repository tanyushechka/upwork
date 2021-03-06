<?php
namespace App\Classes;

class Upwork
{
    public $id;
    public $url;
    public $created_at;
    public $title;
    public $description;
    public $type;
    public $budget;
    public $engagement;
    public $engagement_weeks;
    public $contractor_tier;
    public $skills;
    public $rating;

    public static function findAll($db)
    {
        $sql = 'SELECT * FROM `upwork` WHERE `created_at`>(UNIX_TIMESTAMP()-172800)';
        return $db->dbSelectObj($sql);
    }

    public static function findOne($db, $job_id)
    {
        $class = static::class;
        $sql = 'SELECT * FROM upwork WHERE job_id = :job_id';
        return $db->dbSelect($class, $sql, [':job_id' => $job_id])[0];
    }

    public function insert($db)
    {
        $properties = get_object_vars($this);
        $columns = array_keys($properties);
        $places = [];
        $data = [];
        foreach ($columns as $property) {
            $places[] = ':' . $property;
            $data[':' . $property] = $this->$property;
        }
        $sql = 'INSERT INTO upwork (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $places) . ')';
        return $db->dbExecute($sql, $data);
    }

}