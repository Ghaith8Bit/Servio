<?php

namespace Mrclutch\Servio\Supports;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mrclutch\Servio\Supports\Traits\ApiResponseTrait;

abstract class DTO
{
    use ApiResponseTrait;
    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter((array) $this, fn($attribute) => !is_null($attribute));
    }

    /**
     * Validate the data based on DTO-specific rules.
     *
     * @param array $data
     * @return \Illuminate\Validation\Validator|null
     */
    protected static function validate(array $data)
    {
        $rules = self::extractRules();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $validator;
        }

        return null;
    }

    /**
     * Define validation rules for the DTO.
     *
     * @return array
     */
    abstract protected static function rules(): array;

    /**
     * Create a DTO instance from request data.
     *
     * @param array $data
     * @return static
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(array $data): self
    {
        $validator = static::validate($data);
        if ($validator && $validator->fails()) {
            $instance = new static();
            $response = $instance->errorResponse('Invalid data', 422, $validator->errors()->toArray());
            throw new ValidationException($validator, $response);
        }

        return new static($data);
    }

    /**
     * Validation rules for store.
     *
     * @return array
     */
    private static function StoreRules(): array
    {
        return static::rules();
    }

    /**
     * Validation rules for update.
     *
     * @return array
     */
    private static function UpdateRules(): array
    {
        $rules = [];

        foreach (static::rules() as $name => $validation) {
            $validation = str_replace('required', 'sometimes', $validation);

            if (strpos($name, 'password') !== false) {
                $validation = str_replace('confirmed', '', $validation);
            }

            if (strpos($validation, 'unique:') !== false) {
                $validation = preg_replace('/unique:(\w+),(\w+)/', 'unique:$1,$2,' . request()->route('id'), $validation);
            }

            $validation = rtrim($validation, '|');

            $rules[$name] = $validation;
        }

        return $rules;
    }

    /**
     * Extract the appropriate rules based on the request method.
     *
     * @return array
     */
    private static function extractRules(): array
    {
        return request()->method() === 'PATCH' ? self::UpdateRules() : self::StoreRules();
    }
}
