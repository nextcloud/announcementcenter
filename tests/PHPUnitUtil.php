<?php

namespace OCA\AnnouncementCenter\Tests;

class PHPUnitUtil {
	public static function callHiddenMethod($obj, $name, array $args) {
		$class = new \ReflectionClass($obj);
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method->invokeArgs($obj, $args);
	}
}
