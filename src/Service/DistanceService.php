<?php

namespace App\Service;
use Doctrine\DBAL\Driver\Connection;

class DistanceService 
{
    private $conn;

    public function __construct(Connection $conn) 
    {
        $this->conn = $conn;
    }

    public function getVenuesByDistance(float $latitude, float $longitude, int $radius)
    {
        // 6371 Earth radius in km, thanks Google
        $query = 'SELECT id FROM
            (SELECT id, (6371 * acos(cos(radians( :latitude )) * cos(radians(latitude)) *
            cos(radians(longitude) - radians( :longitude )) +
            sin(radians( :latitude )) * sin(radians(latitude))))
            AS distance
            FROM venues) AS distance
            WHERE distance < :radius
            ORDER BY distance';

        $statement = $this->conn->executeQuery(
            $query,
            ['latitude' => $latitude, 'longitude' => $longitude, 'radius' => $radius],
            ['latitude' => \PDO::PARAM_STR, 'longitude' => \PDO::PARAM_STR, 'radius' => \PDO::PARAM_STR]
        );

        return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
}