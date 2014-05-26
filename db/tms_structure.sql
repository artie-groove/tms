-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1
-- Время создания: Май 26 2014 г., 02:22
-- Версия сервера: 5.5.25
-- Версия PHP: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `tms`
--

-- --------------------------------------------------------

--
-- Структура таблицы `departments`
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'наименование кафедры',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=16384 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `disciplines`
--

CREATE TABLE IF NOT EXISTS `disciplines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=712 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `disciplines_shortenings`
--

CREATE TABLE IF NOT EXISTS `disciplines_shortenings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shortening` varchar(255) NOT NULL COMMENT 'сокращение',
  `id_discipline` int(11) NOT NULL COMMENT 'id дисциплины',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(8) NOT NULL COMMENT 'наименование группы',
  `year` tinyint(4) unsigned NOT NULL COMMENT 'курс',
  `form` enum('FULLTIME','EVENING','EXTRAMURAL','SECOND') NOT NULL COMMENT 'форма обучения: полная (дневная), вечерняя (очно-заочная), заочная, второе высшее',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=2340 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Структура таблицы `lecturers`
--

CREATE TABLE IF NOT EXISTS `lecturers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_department` int(255) NOT NULL COMMENT 'id кафедры',
  `name` varchar(50) NOT NULL COMMENT 'имя',
  `surname` varchar(50) NOT NULL COMMENT 'фамилия',
  `patronymic` varchar(50) DEFAULT NULL COMMENT 'отчество',
  `position` enum('ASSISTANT','SENIOR_LECTURER','DOCENT','PROFESSOR') NOT NULL COMMENT 'должность (ассистент, старший преподаватель, доцент, профессор)',
  PRIMARY KEY (`id`),
  KEY `FK_lecturer_department_ID_dep` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=303 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'номер аудитории',
  `building` enum('A','B','B2','D','V','S') NOT NULL COMMENT 'корпус (А, Б, БЛК, Д, В, Спорткомплекс',
  `floor` tinyint(3) unsigned NOT NULL COMMENT 'этаж',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=585 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `timetable`
--

CREATE TABLE IF NOT EXISTS `timetable` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_discipline` int(11) DEFAULT NULL COMMENT 'id дисциплины',
  `id_group` int(11) DEFAULT NULL COMMENT 'id группы',
  `id_lecturer` int(11) DEFAULT NULL COMMENT 'id преподавателя',
  `id_room` tinyint(4) DEFAULT NULL COMMENT 'id аудитории',
  `offset` tinyint(4) DEFAULT NULL COMMENT 'номер пары: 0 = 8:00, 1 = 9:00, ..., 7 = 19:30',
  `date` date DEFAULT NULL COMMENT 'дата занятия',
  `type` enum('LECTURE','WORKSHOP','LAB') DEFAULT NULL COMMENT 'тип занятия (лекция, практика, лаба)',
  `comment` varchar(255) DEFAULT NULL COMMENT 'примечание',
  PRIMARY KEY (`id`),
  KEY `FK_Timetable_classroom_ID_Classroom` (`id_lecturer`),
  KEY `FK_Timetable_groups_ID_group` (`id_discipline`),
  KEY `FK_Timetable_lecturer_ID_Lecturer` (`id_group`),
  KEY `FK_timetable_subject_ID_Subject` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=91 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
