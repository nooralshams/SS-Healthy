-- Migration : blocage des comptes utilisateurs
-- À exécuter dans phpMyAdmin ou via MySQL

USE zig_healthy_db;

ALTER TABLE utilisateurs
ADD COLUMN bloque TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = compte bloqué';
ALTER TABLE utilisateurs
ADD COLUMN tentatives_connexion TINYINT UNSIGNED NOT NULL DEFAULT 0;
