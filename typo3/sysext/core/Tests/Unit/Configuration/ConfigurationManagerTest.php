<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfigurationFileLocation',
                'getLocalConfigurationFileLocation',
            ]
        );
    }

    /**
     * @param array $methods
     */
    protected function createSubjectWithMockedMethods(array $methods): void
    {
        $this->subject = $this->getMockBuilder(ConfigurationManager::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function getDefaultConfigurationExecutesDefinedDefaultConfigurationFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1310203814);

        $defaultConfigurationFile = Environment::getVarPath() . '/tests/' . $this->getUniqueId('defaultConfiguration');
        file_put_contents(
            $defaultConfigurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203814); ?>'
        );
        $this->testFilesToDelete[] = $defaultConfigurationFile;

        $this->subject
            ->expects($this->once())
            ->method('getDefaultConfigurationFileLocation')
            ->will($this->returnValue($defaultConfigurationFile));
        $this->subject->getDefaultConfiguration();
    }

    /**
     * @test
     */
    public function getLocalConfigurationExecutesDefinedConfigurationFile(): void
    {
        $this->expectException(\RuntimeException::class);

        $configurationFile = Environment::getVarPath() . '/tests/' . $this->getUniqueId('localConfiguration');
        file_put_contents(
            $configurationFile,
            '<?php throw new \RuntimeException(\'foo\', 1310203815); ?>'
        );
        $this->testFilesToDelete[] = $configurationFile;

        $this->subject
            ->expects($this->once())
            ->method('getLocalConfigurationFileLocation')
            ->will($this->returnValue($configurationFile));
        $this->subject->getLocalConfiguration();
    }

    /**
     * @test
     */
    public function updateLocalConfigurationWritesNewMergedLocalConfigurationArray(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'changed' => 'unChanged',
        ];
        $overrideConfiguration = [
            'changed' => 'changed',
            'new' => 'new'
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'changed' => 'changed',
            'new' => 'new',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $this->subject->updateLocalConfiguration($overrideConfiguration);
    }

    /**
     * @test
     */
    public function getDefaultConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getDefaultConfiguration')
            ->will($this->returnValue(
                [
                    'path' => 'value',
                ]
            ));

        $this->assertSame('value', $this->subject->getDefaultConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getLocalConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue(
                [
                    'path' => 'value',
                ]
            ));

        $this->assertSame('value', $this->subject->getLocalConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function getConfigurationValueByPathReturnsCorrectValue(): void
    {
        $this->createSubjectWithMockedMethods(
            [
                'getDefaultConfiguration',
                'getLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getDefaultConfiguration')
            ->will($this->returnValue(
                [
                    'path' => 'value',
                ]
            ));
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue(
                [
                    'path' => 'valueOverride',
                ]
            ));

        $this->assertSame('valueOverride', $this->subject->getConfigurationValueByPath('path'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathReturnFalseIfPathIsNotValid(): void
    {
        $this->createSubjectWithMockedMethods([
            'isValidLocalConfigurationPath',
        ]);
        $this->subject->expects($this->once())
            ->method('isValidLocalConfigurationPath')
            ->will($this->returnValue(false));

        $this->assertFalse($this->subject->setLocalConfigurationValueByPath('path', 'value'));
    }

    /**
     * @test
     */
    public function setLocalConfigurationValueByPathUpdatesValueDefinedByPath(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'notUpdated',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'updated',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'isValidLocalConfigurationPath',
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('isValidLocalConfigurationPath')
            ->will($this->returnValue(true));
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $this->subject->setLocalConfigurationValueByPath('toUpdate', 'updated');
    }

    /**
     * @test
     */
    public function setLocalConfigurationValuesByPathValuePairsSetsPathValuePairs(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'notUpdated',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
            'toUpdate' => 'updated',
            'new' => 'new',
        ];

        $this->createSubjectWithMockedMethods(
            [
                'isValidLocalConfigurationPath',
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->any())
            ->method('isValidLocalConfigurationPath')
            ->will($this->returnValue(true));
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $pairs = [
            'toUpdate' => 'updated',
            'new' => 'new'
        ];
        $this->subject->setLocalConfigurationValuesByPathValuePairs($pairs);
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathRemovesGivenPathsFromConfigurationAndReturnsTrue(): void
    {
        $currentLocalConfiguration = [
            'toRemove1' => 'foo',
            'notChanged' => 23,
            'toRemove2' => 'bar',
        ];
        $expectedConfiguration = [
            'notChanged' => 23,
        ];

        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($expectedConfiguration);

        $removePaths = [
            'toRemove1',
            'toRemove2',
        ];
        $this->assertTrue($this->subject->removeLocalConfigurationKeysByPath($removePaths));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfNothingIsRemoved(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
        ];
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->never())
            ->method('writeLocalConfiguration');

        $removeNothing = [];
        $this->assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNothing));
    }

    /**
     * @test
     */
    public function removeLocalConfigurationKeysByPathReturnsFalseIfSomethingInexistentIsRemoved(): void
    {
        $currentLocalConfiguration = [
            'notChanged' => 23,
        ];
        $this->createSubjectWithMockedMethods(
            [
                'getLocalConfiguration',
                'writeLocalConfiguration',
            ]
        );
        $this->subject->expects($this->once())
            ->method('getLocalConfiguration')
            ->will($this->returnValue($currentLocalConfiguration));
        $this->subject->expects($this->never())
            ->method('writeLocalConfiguration');

        $removeNonExisting = ['notPresent'];
        $this->assertFalse($this->subject->removeLocalConfigurationKeysByPath($removeNonExisting));
    }

    /**
     * @test
     */
    public function canWriteConfigurationReturnsFalseIfLocalConfigurationFileIsNotWritable(): void
    {
        if (\function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);

        $file = '../typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile = Environment::getLegacyConfigPath() . '/' . $file;
        touch($absoluteFile);
        $this->testFilesToDelete[] = $absoluteFile;
        chmod($absoluteFile, 0444);
        clearstatcache();

        $subject->_set('localConfigurationFile', $file);

        $result = $subject->canWriteConfiguration();

        chmod($absoluteFile, 0644);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function canWriteConfigurationReturnsTrueIfDirectoryAndFilesAreWritable(): void
    {
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);

        $directory = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteDirectory = Environment::getPublicPath() . '/' . $directory;
        mkdir($absoluteDirectory);

        $file = 'typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile1 = Environment::getPublicPath() . '/' . $file;
        touch($absoluteFile1);
        $this->testFilesToDelete[] = $absoluteFile1;
        $subject->_set('localConfigurationFile', $absoluteFile1);

        clearstatcache();

        $result = $subject->canWriteConfiguration();

        $this->assertTrue($result);
        $this->testFilesToDelete[] = $absoluteDirectory;
    }

    /**
     * @test
     */
    public function writeLocalConfigurationWritesSortedContentToConfigurationFile(): void
    {
        $configurationFile = Environment::getVarPath() . '/tests/' . $this->getUniqueId('localConfiguration');
        if (!is_file($configurationFile)) {
            if (!$fh = fopen($configurationFile, 'wb')) {
                $this->markTestSkipped('Can not create file ' . $configurationFile . '. Please check your write permissions.');
            }
            fclose($fh);
        }

        if (!@is_file($configurationFile)) {
            throw new \RuntimeException(
                'File ' . $configurationFile . ' could not be found. Please check your write permissions',
                1346364362
            );
        }
        $this->testFilesToDelete[] = $configurationFile;

        $this->subject
            ->expects($this->any())
            ->method('getLocalConfigurationFileLocation')
            ->will($this->returnValue($configurationFile));

        $pairs = [
            'foo' => 42,
            'bar' => 23
        ];
        $expectedContent =
            '<?php' . LF .
            'return [' . LF .
            '    \'bar\' => 23,' . LF .
            '    \'foo\' => 42,' . LF .
            '];' . LF;

        $this->subject->writeLocalConfiguration($pairs);
        $this->assertSame($expectedContent, file_get_contents($configurationFile));
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationThrowsExceptionIfFileExists(): void
    {
        $this->expectException(\RuntimeException::class);

        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);

        $file = '../typo3temp/var/tests/' . $this->getUniqueId('test_');
        $absoluteFile = Environment::getLegacyConfigPath() . '/' . $file;
        touch($absoluteFile);
        $this->testFilesToDelete[] = $absoluteFile;
        $subject->_set('localConfigurationFile', $file);

        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationWritesContentFromFactoryFile(): void
    {
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['writeLocalConfiguration']);
        $subject->_set('localConfigurationFile', '../../typo3temp/var/tests/' . $this->getUniqueId('dummy_'));

        $factoryConfigurationFile = '../../typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = Environment::getFrameworkBasePath() . '/' . $factoryConfigurationFile;
        $uniqueContentString = $this->getUniqueId('string_');
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [' . LF .
            '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
            '];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $factoryConfigurationAbsoluteFile;

        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $subject
            ->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($this->arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function createLocalConfigurationFromFactoryConfigurationMergesConfigurationWithAdditionalFactoryFile(): void
    {
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['writeLocalConfiguration']);
        $subject->_set('localConfigurationFile', '../../typo3temp/var/tests/' . $this->getUniqueId('dummy_'));

        $factoryConfigurationFile = '../../typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $factoryConfigurationAbsoluteFile = Environment::getFrameworkBasePath() . '/' . $factoryConfigurationFile;
        $validFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [];' . LF;
        file_put_contents(
            $factoryConfigurationAbsoluteFile,
            $validFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $factoryConfigurationAbsoluteFile;
        $subject->_set('factoryConfigurationFile', $factoryConfigurationFile);

        $additionalFactoryConfigurationFile = 'typo3temp/var/tests/' . $this->getUniqueId('test_') . '.php';
        $additionalFactoryConfigurationAbsoluteFile = Environment::getPublicPath() . '/' . $additionalFactoryConfigurationFile;
        $uniqueContentString = $this->getUniqueId('string_');
        $validAdditionalFactoryConfigurationFileContent =
            '<?php' . LF .
            'return [' . LF .
            '\'' . $uniqueContentString . '\' => \'foo\',' . LF .
            '];' . LF;
        file_put_contents(
            $additionalFactoryConfigurationAbsoluteFile,
            $validAdditionalFactoryConfigurationFileContent
        );
        $this->testFilesToDelete[] = $additionalFactoryConfigurationAbsoluteFile;
        $subject->_set('additionalFactoryConfigurationFile', $additionalFactoryConfigurationFile);

        $subject
            ->expects($this->once())
            ->method('writeLocalConfiguration')
            ->with($this->arrayHasKey($uniqueContentString));
        $subject->createLocalConfigurationFromFactoryConfiguration();
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathAcceptsWhitelistedPath(): void
    {
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);
        $subject->_set('whiteListedLocalConfigurationPaths', ['foo/bar']);
        $this->assertTrue($subject->_call('isValidLocalConfigurationPath', 'foo/bar/baz'));
    }

    /**
     * @test
     */
    public function isValidLocalConfigurationPathDeniesNotWhitelistedPath(): void
    {
        /** @var $subject ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(ConfigurationManager::class, ['dummy']);
        $subject->_set('whiteListedLocalConfigurationPaths', ['foo/bar']);
        $this->assertFalse($subject->_call('isValidLocalConfigurationPath', 'bar/baz'));
    }
}
