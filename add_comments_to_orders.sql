-- SQL Migration to add comments field to orders table
ALTER TABLE `orders` ADD COLUMN `comments` TEXT AFTER `text_line4`;
