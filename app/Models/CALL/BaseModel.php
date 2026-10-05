<?php

namespace App\Models\CALL;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'call';

    /**
     * Get the table associated with the model.
     * By default, use the snake_case plural of the class name.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }

    /**
     * Create a new model instance using the call connection.
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $instance = new static;
        $instance->setConnection('call');
        $instance->fill($attributes)->save();
        return $instance;
    }

    /**
     * Begin querying the model on the call connection.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function onCallConnection()
    {
        return static::on('call');
    }
}
