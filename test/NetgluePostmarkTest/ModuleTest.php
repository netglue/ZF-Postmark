<?php
declare(strict_types=1);

namespace NetgluePostmarkTest;

use NetgluePostmark\Module;

class ModuleTest extends TestCase
{

    public function testModuleMethodsReturnArrays()
    {
        $module = new Module();
        $this->assertInternalType('array', $module->getConfig());
        $this->assertInternalType('array', $module->getControllerConfig());
        $this->assertInternalType('array', $module->getServiceConfig());
    }
}
