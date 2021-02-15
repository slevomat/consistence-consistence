<?php

declare(strict_types = 1);

namespace Consistence\Enum;

use Consistence\Reflection\ClassReflection;
use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use ReflectionClass;
use ReflectionClassConstant;

abstract class Enum extends \Consistence\ObjectPrototype
{

	/** @var mixed */
	private $value;

	/** @var self[] indexed by enum and value */
	private static $instances = [];

	/** @var mixed[] format: enum name (string) => cached values (const name (string) => value (mixed)) */
	private static $availableValues;

	/**
	 * @param mixed $value
	 */
	final private function __construct($value)
	{
		static::checkValue($value);
		$this->value = $value;
	}

	/**
	 * @param mixed $value
	 * @return static
	 */
	public static function get($value): self
	{
		$index = sprintf('%s::%s', static::class, $value);
		if (!isset(self::$instances[$index])) {
			self::$instances[$index] = new static($value);
		}

		return self::$instances[$index];
	}

	/**
	 * @return mixed[]
	 */
	public static function getAvailableValues(): iterable
	{
		$index = static::class;
		if (!isset(self::$availableValues[static::class])) {
			self::$availableValues[$index] = self::getEnumConstants();
		}

		return self::$availableValues[$index];
	}

	/**
	 * @return static[]
	 */
	public static function getAvailableEnums(): iterable
	{
		$values = static::getAvailableValues();
		return ArrayType::mapByCallback($values, function (KeyValuePair $pair) {
			return new KeyValuePair($pair->getKey(), static::get($pair->getValue()));
		});
	}

	/**
	 * @return mixed[] format: const name (string) => value (mixed)
	 */
	private static function getEnumConstants(): array
	{
		$classReflection = new ReflectionClass(static::class);
		$declaredConstants = ClassReflection::getDeclaredConstants($classReflection);
		$declaredPublicConstants = ArrayType::filterValuesByCallback(
			$declaredConstants,
			function (ReflectionClassConstant $constant): bool {
				return $constant->isPublic();
			}
		);

		return ArrayType::mapByCallback(
			$declaredPublicConstants,
			function (KeyValuePair $keyValuePair): KeyValuePair {
				$constant = $keyValuePair->getValue();
				assert($constant instanceof ReflectionClassConstant);

				return new KeyValuePair(
					$constant->getName(),
					$constant->getValue()
				);
			}
		);
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function isValidValue($value): bool
	{
		return ArrayType::containsValue(static::getAvailableValues(), $value);
	}

	/**
	 * @param mixed $value
	 */
	public static function checkValue($value): void
	{
		if (!static::isValidValue($value)) {
			throw new \Consistence\Enum\InvalidEnumValueException($value, static::class);
		}
	}

	protected function checkSameEnum(self $that): void
	{
		if (get_class($this) !== get_class($that)) {
			throw new \Consistence\Enum\OperationSupportedOnlyForSameEnumException($that, $this);
		}
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	public function equals(self $that): bool
	{
		$this->checkSameEnum($that);

		return $this === $that;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function equalsValue($value): bool
	{
		return $this->getValue() === $value;
	}

}
