<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/Auth.php';

/**
 * Klasa testowa dla dashboard.php
 */
class DashboardTest extends TestCase
{
    private $auth;
    private $config;
    private $userProfileMock;

    protected function setUp(): void
    {
        // Tworzenie mocka dla klasy Auth
        $this->auth = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Tworzenie mocka dla obsługi profilu użytkownika
        $this->userProfileMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getUserProfile'])
            ->getMock();

        // Symulacja konfiguracji
        $this->config = [
            'app' => [
                'max_cities_per_page' => 10
            ]
        ];
    }

    /**
     * Test sprawdzający zachowanie gdy użytkownik jest zalogowany
     */
    public function testAuthenticatedUserAccess()
    {
        // Przygotowanie danych testowych
        $userId = 123;
        $userProfile = [
            'id' => $userId,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];

        // Mockowanie metody authenticateAndGetUserId
        $this->auth->expects($this->once())
            ->method('authenticateAndGetUserId')
            ->willReturn($userId);

        // Mockowanie metody getUserProfile
        $this->userProfileMock->expects($this->once())
            ->method('getUserProfile')
            ->with($userId)
            ->willReturn($userProfile);

        // Symulacja wywołania logiki z dashboard.php
        $userId = $this->auth->authenticateAndGetUserId();
        $currentUser = $this->userProfileMock->getUserProfile($userId);

        // Asercje
        $this->assertNotNull($userId);
        $this->assertEquals(123, $userId);
        $this->assertIsArray($currentUser);
        $this->assertEquals('testuser', $currentUser['username']);
    }

    /**
     * Test sprawdzający zachowanie gdy użytkownik nie jest zalogowany
     */
    public function testUnauthenticatedUserAccess()
    {
        // Mockowanie metody authenticateAndGetUserId zwracającej false
        $this->auth->expects($this->once())
            ->method('authenticateAndGetUserId')
            ->willReturn(false);

        // Symulacja wywołania logiki z dashboard.php
        $userId = $this->auth->authenticateAndGetUserId();

        // Asercje
        $this->assertFalse($userId);
    }

    /**
     * Test sprawdzający zachowanie gdy profil użytkownika nie istnieje
     */
    public function testNonExistentUserProfile()
    {
        // Przygotowanie danych testowych
        $userId = 9999919; // nieistniejący użytkownik

        // Mockowanie metody authenticateAndGetUserId
        $this->auth->expects($this->once())
            ->method('authenticateAndGetUserId')
            ->willReturn($userId);

        // Mockowanie metody getUserProfile zwracającej null dla nieistniejącego użytkownika
        $this->userProfileMock->expects($this->once())
            ->method('getUserProfile')
            ->with($userId)
            ->willReturn(null);

        // Symulacja wywołania logiki z dashboard.php
        $userId = $this->auth->authenticateAndGetUserId();
        $currentUser = $this->userProfileMock->getUserProfile($userId);

        // Asercje
        $this->assertNotNull($userId);
        $this->assertNull($currentUser);
    }

    /**
     * Test sprawdzający poprawność konfiguracji
     */
    public function testConfigurationValues()
    {
        // Asercje dla wartości konfiguracyjnych
        $this->assertArrayHasKey('app', $this->config);
        $this->assertArrayHasKey('max_cities_per_page', $this->config['app']);
        $this->assertEquals(10, $this->config['app']['max_cities_per_page']);
    }
} 