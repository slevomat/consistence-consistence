<?php

declare(strict_types = 1);

namespace Consistence\Enum;

use Consistence\Reflection\ClassReflection;
use ReflectionClass;
use ReflectionClassConstant;
use function array_filter;
use function in_array;

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
		$enums = [];

		foreach (static::getAvailableValues() as $key => $value) {
			$enums[$key] = static::get($value);
		}

		return $enums;
	}

	/**
	 * @return mixed[] format: const name (string) => value (mixed)
	 */
	private static function getEnumConstants(): array
	{
		$classReflection = new ReflectionClass(static::class);
		$declaredConstants = ClassReflection::getDeclaredConstants($classReflection);

		/** @var \ReflectionClassConstant $declaredPublicConstants */
		$declaredPublicConstants = array_filter(
			$declaredConstants,
			function (ReflectionClassConstant $constant): bool {
				return $constant->isPublic();
			}
		);

		$enumConstants = [];

		foreach ($declaredPublicConstants as $declaredPublicConstant) {
			$enumConstants[$declaredPublicConstant->getName()] = $declaredPublicConstant->getValue();
		}

		return $enumConstants;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function isValidValue($value): bool
	{
		return in_array($value, static::getAvailableValues(), true);
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
