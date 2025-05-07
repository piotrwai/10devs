<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/ErrorLogger.php';
require_once __DIR__ . '/../../classes/GeoHelper.php';

class GeoHelperTest extends TestCase
{
    private $geoHelper;

    protected function setUp(): void
    {
        // Tworzymy instancję klasy GeoHelper
        $this->geoHelper = new GeoHelper();
    }

    /**
     * Testuje metodę isCity z poprawną nazwą miasta.
     */
    public function testIsCityWithValidCityName()
    {
        // Mockowanie odpowiedzi API Geocoding
        $mockResponse = [
            'status' => 'OK',
            'results' => [
                [
                    'types' => ['locality'],
                    'address_components' => [
                        [
                            'types' => ['locality'],
                            'long_name' => 'Kraków'
                        ]
                    ]
                ]
            ]
        ];

        // Mockowanie metody performCurlRequest
        $geoHelperMock = $this->getMockBuilder(GeoHelper::class)
            ->onlyMethods(['performCurlRequest'])
            ->getMock();

        $geoHelperMock->expects($this->once())
            ->method('performCurlRequest')
            ->willReturn($mockResponse);

        // Wywołanie metody isCity
        $result = $geoHelperMock->isCity('Kraków');

        // Asercje
        $this->assertIsArray($result);
        $this->assertTrue($result['isCity']);
        $this->assertEquals('Kraków', $result['properName']);
    }

    /**
     * Testuje metodę isCity z niepoprawną nazwą miasta.
     */
    public function testIsCityWithInvalidCityName()
    {
        // Mockowanie odpowiedzi API Geocoding
        $mockResponse = [
            'status' => 'ZERO_RESULTS'
        ];

        // Mockowanie metody performCurlRequest
        $geoHelperMock = $this->getMockBuilder(GeoHelper::class)
            ->onlyMethods(['performCurlRequest'])
            ->getMock();

        $geoHelperMock->expects($this->once())
            ->method('performCurlRequest')
            ->willReturn($mockResponse);

        // Wywołanie metody isCity
        $result = $geoHelperMock->isCity('Nieistniejące Miasto');

        // Asercje
        $this->assertIsArray($result);
        $this->assertFalse($result['isCity']);
        $this->assertEquals('Nieistniejące Miasto', $result['properName']);
    }

    /**
     * Testuje metodę getDirections z poprawnymi miastami.
     */
    public function testGetDirectionsWithValidCities()
    {
        // Mockowanie odpowiedzi API Directions
        $mockResponse = [
            'status' => 'OK',
            'routes' => [
                [
                    'legs' => [
                        [
                            'distance' => ['value' => 10000], // 10 km
                            'steps' => [
                                [
                                    'html_instructions' => 'Jedź prosto',
                                    'distance' => ['text' => '1 km']
                                ]
                            ]
                        ]
                    ],
                    'summary' => 'Trasa A4'
                ]
            ]
        ];

        // Mockowanie metody performCurlRequest
        $geoHelperMock = $this->getMockBuilder(GeoHelper::class)
            ->onlyMethods(['performCurlRequest'])
            ->getMock();

        $geoHelperMock->expects($this->once())
            ->method('performCurlRequest')
            ->willReturn($mockResponse);

        // Wywołanie metody getDirections
        $result = $geoHelperMock->getDirections('Kraków', 'Warszawa');

        // Asercje
        $this->assertIsArray($result);
        $this->assertEquals(10, $result['distance_km']);
        $this->assertCount(1, $result['steps']);
        $this->assertEquals('Trasa A4', $result['summary']);
    }

    /**
     * Testuje metodę getDirections z niepoprawnymi miastami.
     */
    public function testGetDirectionsWithInvalidCities()
    {
        // Mockowanie odpowiedzi API Directions
        $mockResponse = [
            'status' => 'ZERO_RESULTS'
        ];

        // Mockowanie metody performCurlRequest
        $geoHelperMock = $this->getMockBuilder(GeoHelper::class)
            ->onlyMethods(['performCurlRequest'])
            ->getMock();

        $geoHelperMock->expects($this->once())
            ->method('performCurlRequest')
            ->willReturn($mockResponse);

        // Wywołanie metody getDirections
        $result = $geoHelperMock->getDirections('Nieistniejące Miasto', 'Inne Nieistniejące Miasto');

        // Asercje
        $this->assertNull($result);
    }

    /**
     * Testuje metodę performCurlRequest z błędem cURL.
     */
    public function testPerformCurlRequestWithCurlError()
    {
        // Mockowanie cURL
        $geoHelperMock = $this->getMockBuilder(GeoHelper::class)
            ->onlyMethods(['performCurlRequest'])
            ->getMock();

        $geoHelperMock->expects($this->once())
            ->method('performCurlRequest')
            ->willReturn(null);

        // Wywołanie metody isCity (która używa performCurlRequest)
        $result = $geoHelperMock->isCity('Kraków');

        // Asercje
        $this->assertFalse($result);
    }
}