<?php

namespace App\Controller;

use App\Entity\Weather;
use App\Service\WeatherUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherApiController extends AbstractController
{
    #[Route('/api/v1/weather', name: 'app_weather_api', methods: ['GET'])]
    public function index(
        WeatherUtil $weatherUtil,
        #[MapQueryParameter] string $country,
        #[MapQueryParameter] string $city,
        #[MapQueryParameter] string $format = 'json',
        #[MapQueryParameter('twig')] bool $twig = false,
    ): Response {
        $measurements = $weatherUtil->getWeatherForCountryAndCity($country, $city);

        if ($twig) {
            if ($format === 'csv') {
                return new Response(
                    $this->renderView('weather_api/index.csv.twig', [
                        'city' => $city,
                        'country' => $country,
                        'measurements' => $measurements,
                    ]),
                    200,
                    ['Content-Type' => 'text/plain; charset=utf-8'],
                );
            }

            return new Response(
                $this->renderView('weather_api/index.json.twig', [
                    'city' => $city,
                    'country' => $country,
                    'measurements' => $measurements,
                ]),
                200,
                ['Content-Type' => 'application/json'],
            );
        }

        if ($format === 'csv') {
            $rows = array_map(
                fn (Weather $m) => implode(',', [
                    $city,
                    $country,
                    $m->getDate()->format('Y-m-d'),
                    $m->getCelsius(),
                    $m->getFahrenheit(),
                ]),
                $measurements,
            );

            return new Response(
                implode("\n", $rows).(count($rows) > 0 ? "\n" : ''),
                200,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        return $this->json([
            'city' => $city,
            'country' => $country,
            'measurements' => array_map(fn (Weather $m) => [
                'date' => $m->getDate()->format('Y-m-d'),
                'celsius' => $m->getCelsius(),
                'fahrenheit' => $m->getFahrenheit(),
            ], $measurements),
        ]);
    }
}
