<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20200124074323 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE `bands` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ');


        $this->addSql('
            CREATE TABLE `venues` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `name` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
            `latitude` decimal(10,8) NOT NULL,
            `longitude` decimal(11,8) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ');


        $this->addSql('
            CREATE TABLE `concerts` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `venueId` bigint(20) NOT NULL,
            `bandId` bigint(10) NOT NULL,
            `date` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`),
            KEY `venueId` (`venueId`),
            KEY `bandId` (`bandId`),
            CONSTRAINT `concerts_ibfk_1` FOREIGN KEY (`venueId`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
            CONSTRAINT `concerts_ibfk_2` FOREIGN KEY (`bandId`) REFERENCES `bands` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE concerts');
        $this->addSql('DROP TABLE venues');
        $this->addSql('DROP TABLE bands');

    }
}
