<?php

namespace App\Command;

use App\Repository\LocationRepository;
use App\Service\WeatherUtil;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'weather:city',
    description: 'Get weather forecast for a city by country code and city name',
)]
class WeatherCityCommand
{
    public function __construct(
        private readonly LocationRepository $locationRepository,
        private readonly WeatherUtil $weatherUtil,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Country code (e.g. PL)')] string $country,
        #[Argument('City name')] string $city,
    ): int {
        $location = $this->locationRepository->findOneBy([
            'country' => $country,
            'city' => $city,
        ]);

        if ($location === null) {
            $io->error(sprintf('Location %s, %s not found.', $city, $country));

            return Command::FAILURE;
        }

        $measurements = $this->weatherUtil->getWeatherForCountryAndCity($country, $city);

        $io->writeln(sprintf('Location: %s', $location->getCity()));
        $io->table(
            ['Date', 'Temperature'],
            array_map(fn ($m) => [
                $m->getDate()->format('Y-m-d'),
                $m->getTemperature(),
            ], $measurements),
        );

        return Command::SUCCESS;
    }
}
