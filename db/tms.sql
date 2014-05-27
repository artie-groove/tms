-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1
-- Время создания: Май 27 2014 г., 13:32
-- Версия сервера: 5.5.25
-- Версия PHP: 5.2.12

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=712 AUTO_INCREMENT=713 ;

--
-- Дамп данных таблицы `disciplines`
--

INSERT INTO `disciplines` (`id`, `name`) VALUES
(1, 'CAD/CAM/CAE'),
(2, 'Автоматизация измерений, контроля и испытаний'),
(3, 'Автоматизация производственных процессов'),
(4, 'Автоматизация производственных процессов в машиностроении'),
(5, 'Автоматизация технологических процессов и производств'),
(6, 'Автоматизация управления жизненным циклом продукции'),
(7, 'Автоматизированные системы проектирования'),
(8, 'Автоматизированные системы управления'),
(9, 'Автоматизированные системы управления технологическими процессами'),
(10, 'Автоматизированный электропривод'),
(11, 'Автоматизированный электропривод станков и промышленных роботов'),
(12, 'Автоматизированный электропривод технологического оборудования'),
(13, 'Автоматика и автоматизация на транспорте'),
(14, 'Автомобили. Конструкция'),
(15, 'Автомобили. Расчёт'),
(16, 'Автомобили. Теория'),
(17, 'Автомобили. Транспортные средства'),
(18, 'Автомобильные двигатели'),
(19, 'Автосервис и фирменное обслуживание автомобилей'),
(20, 'Администрирование операционных систем'),
(21, 'Алгебра и геометрия'),
(22, 'Анализ и диагностика финансово-хозяйственной деятельности предприятия'),
(23, 'Анализ и синтез ХТС'),
(24, 'Анализ состоятельности предприятия'),
(25, 'Анализ управления затратами'),
(26, 'Анализ управления финансами'),
(27, 'Анализ хозяйственной деятельности'),
(28, 'Анализ эффективности систем управления'),
(29, 'Аналитическая химия и физико-химические методы анализа'),
(30, 'Аналитическое программное обеспечение'),
(31, 'Антикризисное управление'),
(32, 'Антикризисный менеджмент'),
(33, 'Аппаратное и программное обеспечение графических работ'),
(34, 'Архитектура ЭВМ'),
(35, 'Аудит'),
(36, 'Базы данных'),
(37, 'Базы и банки данных'),
(38, 'Банковское дело'),
(39, 'Безопасность движения'),
(40, 'Безопасность жизнедеятельности'),
(41, 'Бизнес-планирование'),
(42, 'Бизнес-планирование на автомобильном транспорте'),
(43, 'Биология и микробиология'),
(44, 'Биотехнологические процессы в промышленности и экологии'),
(45, 'Биохимия'),
(46, 'Бухгалтерский учёт'),
(47, 'Бухгалтерский учёт в промышленности'),
(48, 'Бухгалтерский учёт и анализ'),
(49, 'Бюджетирование'),
(50, 'Бюджетная система РФ'),
(51, 'Введение в инженерное дело'),
(52, 'Введение в механику сплошных сред'),
(53, 'Введение в наноматериалы и нанотехнологии'),
(54, 'Введение в направление'),
(55, 'Введение в параллельное программирование'),
(56, 'Введение в программную инженерию'),
(57, 'Введение в проектирование автоматизированных систем обработки информации и управления'),
(58, 'Введение в специальность'),
(59, 'Введение в термодинамику полимеров'),
(60, 'Введение в ХТ полимеров'),
(61, 'Взаимозаменяемость'),
(62, 'Взаимозаменяемость и нормирование точности'),
(63, 'Внешнеэкономическая деятельность'),
(64, 'Выбор и проектирование заготовок'),
(65, 'Выполнение выпускной работы'),
(66, 'Выполнение выпускной работы бакалавра'),
(67, 'Выполнение квалификационной работы'),
(68, 'Выпускная работа'),
(69, 'Вычислительная техника и сети в транспортной отрасли'),
(70, 'Вычислительная математика'),
(71, 'Вычислительная техника и сети'),
(72, 'Вычислительные машины, системы и сети'),
(73, 'Вычислительные системы. Сети и телекоммуникации'),
(74, 'Газобаллонное оборудование'),
(75, 'Газодинамика'),
(76, 'Гидравлика'),
(77, 'Гидравлика и гидропневмоавтоматика'),
(78, 'Гидравлические и пневматические системы'),
(79, 'Гидравлические и пневматические системы автомобилей и автомобильного транспорта'),
(80, 'Гидравлические и пневматические системы автомобилей и гаражного оборудования'),
(81, 'Гидромеханика'),
(82, 'Гидромеханика и основы гидропривода'),
(83, 'Гидропневмопривод и гидропневмоавтоматика'),
(84, 'Государственное и муниципальное управление'),
(85, 'Государственное регулирование и таможенное дело'),
(86, 'Государственные и муниципальные финансы'),
(87, 'Гранулярные вычисления'),
(88, 'Графические программные среды'),
(89, 'Двигатели внутреннего сгорания'),
(90, 'Деловая этика'),
(91, 'Деловой иностранный язык'),
(92, 'Деловой иностранный язык (английский)'),
(93, 'Деловой иностранный язык (немецкий)'),
(94, 'Деловой русский язык'),
(95, 'Деловые коммуникации'),
(96, 'Деньги, кредит, банки'),
(97, 'Детали машин'),
(98, 'Детали машин и основы конструирования'),
(99, 'Детали машин и ПТУ'),
(100, 'Диагностика и надёжность автоматизированных систем'),
(101, 'Диагностика и организация ремонта'),
(102, 'Диагностика, ремонт и монтаж оборудования'),
(103, 'Диверсификация компетенций в нестандартных ситуациях'),
(104, 'Диверсификация социально-экономического развития'),
(105, 'Дискретная математика'),
(106, 'Дискретно-логические системы управления'),
(107, 'Документирование управленческой деятельности'),
(108, 'Дополнительные главы химии. Химия и технология полимерных покрытий'),
(109, 'Естественно-научные основы современных технологий'),
(110, 'Задачи математической физики'),
(111, 'Защита интеллектуальной собственности'),
(112, 'Защита интеллектуальной собственности и патентоведение'),
(113, 'Защита информации'),
(114, 'Защита окружающей среды в ЧС'),
(115, 'Защита от коррозии'),
(116, 'Инвестиции'),
(117, 'Инвестиционный анализ'),
(118, 'Инженерная графика'),
(119, 'Инженерная графика в отрасли'),
(120, 'Инженерная графика в отрасли (химия)'),
(121, 'Инженерная и компьютерная графика'),
(122, 'Инженерная химия'),
(123, 'Инновационная экономика'),
(124, 'Инновационные технологии производства в нефтегазохимическом комплексе'),
(125, 'Инновационный менеджмент'),
(126, 'Иностранный язык'),
(127, 'Иностранный язык (английский)'),
(128, 'Иностранный язык (второй)'),
(129, 'Иностранный язык (деловое общение)'),
(130, 'Иностранный язык (деловое общение) (английский)'),
(131, 'Иностранный язык (немецкий)'),
(132, 'Иностранный язык (второй) (немецкий)'),
(133, 'Институциональная экономика'),
(134, 'Инструментальные средства программирования (периферийные устройства)'),
(135, 'Инструментальные средства программирования (цифровая обработка сигналов)'),
(136, 'Интегрированная логистическая поддержка продукции на этапах жизненного цикла'),
(137, 'Интегрированные системы проектирования и управления'),
(138, 'Интегрированные системы проектирования и управления автоматизированных и автоматических производств'),
(139, 'Интеллектуальные системы'),
(140, 'Интерфейсы АСОИУ'),
(141, 'Информатика'),
(142, 'Информационное обеспечение автотранспортных систем'),
(143, 'Информационное обеспечение транспортных систем'),
(144, 'Информационные системы в экономике'),
(145, 'Информационные системы управления качеством в автоматизированных и автоматических производствах'),
(146, 'Информационные технологии'),
(147, 'Информационные технологии в менеджменте'),
(148, 'Информационные технологии в процессах переработки полимеров'),
(149, 'Информационные технологии в экономике'),
(150, 'Исследование операций'),
(151, 'Иституциональная экономика'),
(152, 'История'),
(153, 'История и методология науки в машиностроительных производствах'),
(154, 'История и методология науки и производства'),
(155, 'История и методология химической технологиии'),
(156, 'История и методология экономической науки'),
(157, 'История и современное состояние мировой автомобилизации'),
(158, 'История мировой культуры'),
(159, 'История развития техники'),
(160, 'История экономики'),
(161, 'История экономики и экономических учений'),
(162, 'История экономических учений'),
(163, 'История экономической науки '),
(164, 'Квалиметрия и управление качеством'),
(165, 'Кинетика и термодинамика синтеза ВМС'),
(166, 'Количественные методы анализа экономических процессов'),
(167, 'Коллоидная химия'),
(168, 'Компьютерная графика'),
(169, 'Компьютерное моделирование'),
(170, 'Компьютерные методы и информационные системы в технологии полимеров'),
(171, 'Компьютерные методы и информационные системы в технологии синтеза и переработки полимеров'),
(172, 'Компьютерные системы автоматизации и управления'),
(173, 'Компьютерные технологии в науке и производстве'),
(174, 'Компьютерные технологии в науке и образовании'),
(175, 'Компьюторная графика'),
(176, 'Конкурентная и антимонопольная политика'),
(177, 'Конструирование и обслуживание силовых передач'),
(178, 'Конструирование и расчёт элементов оборудования'),
(179, 'Конструирование программного обеспечения'),
(180, 'Контроллинг'),
(181, 'Конфликтология'),
(182, 'Концепции современного естествознания'),
(183, 'Концепции современного естествознания (физика)'),
(184, 'Концепции современного естествознания (химия)'),
(185, 'Корпоративная социальная ответственность'),
(186, 'Корпоративная стратегия'),
(187, 'Корпоративное управление'),
(188, 'Корпоративное управление и право'),
(189, 'Корпоративные финансы'),
(190, 'Культура речи'),
(191, 'Культурология'),
(192, 'Линейная алгебра'),
(193, 'Линейная алгебра и аналитическая геометрия'),
(194, 'Линейные и векторные пространства'),
(195, 'Логика'),
(196, 'Логика и теория алгоритмов'),
(197, 'Логистика'),
(198, 'Логическое исчисление и теория сложности вычислений'),
(199, 'Макроэкономика'),
(200, 'Макроэкономика (продвинутый курс)'),
(201, 'Маркетинг'),
(202, 'Маркетинг и менеджмент на предприятии'),
(203, 'Маркетинг и менеджмент программных систем'),
(204, 'Маркетинг на мировых рынках товаров и услуг'),
(205, 'Маркетинг, менеджмент и организация производства'),
(206, 'Математика'),
(207, 'Математика (спецглавы)'),
(208, 'Математическая логика и теория алгоритмов'),
(209, 'Математические методы обработки экспериментальных данных'),
(210, 'Математические методы оптимального управления'),
(211, 'Математические основы автоматизации'),
(212, 'Математические основы искусственного интеллекта'),
(213, 'Математические основы современной теории управления'),
(214, 'Математические основы теории управления'),
(215, 'Математический анализ'),
(216, 'Математическое моделирование'),
(217, 'Математическое моделирование абразивной обработки'),
(218, 'Математическое моделирование в машиностроении'),
(219, 'Математическое моделирование процессов'),
(220, 'Математическое моделирование стационарных систем'),
(221, 'Математическое моделирование химико-технологических процессов'),
(222, 'Материаловедение'),
(223, 'Материаловедение. Технология конструкционных материалов'),
(224, 'Машинная графика'),
(225, 'Машинно-зависимые языки'),
(226, 'Машинно-ориентированные языки'),
(227, 'Машины и аппараты химических производств'),
(228, 'Междисциплинарный курсовой проект'),
(229, 'Международные валютно-кредитные отношения'),
(230, 'Международные стандарты учёта'),
(231, 'Международный менеджмент'),
(232, 'Международный маркетинг'),
(233, 'Менеджмент'),
(234, 'Металлорежущие станки'),
(235, 'Методология научного творчества'),
(236, 'Методы анализа нечёткой информации'),
(237, 'Методы и средства защиты компьютерной информации'),
(238, 'Методы и средства измерений в экспериментальных исследованиях'),
(239, 'Методы и средства измерений и контроля'),
(240, 'Методы и средства измерений, испытания и контроля'),
(241, 'Методы инженерного творчества'),
(242, 'Методы исследования систем управления'),
(243, 'Методы оптимальных решений'),
(244, 'Методы оптимизации'),
(245, 'Методы оптимизации (Оптимизация технологических процессов)'),
(246, 'Методы передачи и обработки научной информации'),
(247, 'Методы принятия управленческих решений'),
(248, 'Методы финансовых расчётов'),
(249, 'Метрологическое обеспечение машиностроительного производства'),
(250, 'Метрологическое обеспечение технологических процессов'),
(251, 'Метрология'),
(252, 'Метрология программного обеспечения'),
(253, 'Метрология, стандартизация и сертификация'),
(254, 'Механика'),
(255, 'Механика многофазных систем'),
(256, 'Микропроцессорная техника'),
(257, 'Микропроцессорные системы и управленческие комплексы'),
(258, 'Микропроцессорные системы управления'),
(259, 'Микропроцессорные средства автоматизации'),
(260, 'Микроэкономика'),
(261, 'Микроэкономика (продвинутый курс)'),
(262, 'Мировая экономика'),
(263, 'Мировая экономика и международные экономические отношения'),
(264, 'Многопоточные вычисления для автоматизированных систем обработки информации и управления'),
(265, 'Мобильные и встраиваемые операционные системы'),
(266, 'Модели и методы анализа проектных решений'),
(267, 'Модели и методы в экономике'),
(268, 'Моделирование объектов и систем'),
(269, 'Моделирование переработки полимеров'),
(270, 'Моделирование программного обеспечения'),
(271, 'Моделирование процессов управления'),
(272, 'Моделирование систем'),
(273, 'Моделирование систем и процессов'),
(274, 'Моделирование технологических процессов синтеза ВМС'),
(275, 'Моделирование химико-технологических процессов'),
(276, 'Моделирование энерго- и ресурсосберегающих процессов в ХТ, НХ и БТ'),
(277, 'Монтаж и наладка приборов автоматизации'),
(278, 'Монтаж оборудования химической промышленности'),
(279, 'Мультимедийные технологии'),
(280, 'Надёжность и диагностика технологических систем'),
(281, 'Надёжность и качество программных систем'),
(282, 'Надёжность систем управления'),
(283, 'Надёжность технических систем'),
(284, 'Надёжность технических систем и оборудования'),
(285, 'Надёжность, эргономика и качество АСОИУ'),
(286, 'Налоги и налогообложение'),
(287, 'Налогообложение'),
(288, 'Налогообложение предприятий'),
(289, 'Наногетерогенные эластомерные материалы'),
(290, 'Нанотехнологии в машиностроении'),
(291, 'Насосы и компрессоры'),
(292, 'Научная организация труда'),
(293, 'Научные исследования в области конструкторско-технологического обеспечения машиностроительных производств'),
(294, 'Научный семинар по проблемам автоматизированного управления'),
(295, 'Начертательная геометрия'),
(296, 'Начертательная геометрия и инженерная графика'),
(297, 'Нормативы по защите окружающей среды'),
(298, 'Нормирование труда и организация заработной платы'),
(299, 'Оборудование и проектирование химических производств'),
(300, 'Оборудование машиностроительных производств'),
(301, 'Оборудование химических производств'),
(302, 'Оборудование химических процессов'),
(303, 'Общая и неорганическая химия'),
(304, 'Общая теория измерений'),
(305, 'Общая теория рисков'),
(306, 'Общая технология полимерных материалов'),
(307, 'Общая химическая технология'),
(308, 'Общая химическая технология и основы биотехнологии'),
(309, 'Общая химическая технология полимеров'),
(310, 'Общая химическая технология. Инженерная химия'),
(311, 'Общая электротехника'),
(312, 'Общая электротехника и электроника'),
(313, 'Объектно-ориентированный анализ и программирование'),
(314, 'Операционные системы'),
(315, 'Оптимальные и адаптивные системы'),
(316, 'Оптимизация технологических процессов'),
(317, 'Организационно-производственные структуры технической эксплуатации'),
(318, 'Организационно-экономическое проектирование инновационных процессов'),
(319, 'Организация автомобильных перевозок и безопасность движения'),
(320, 'Организация автомобильных перевозок'),
(321, 'Организация автомобильных перевозок и безопасность транспортного процесса'),
(322, 'Организация и планирование автоматизированных производств'),
(323, 'Организация и планирование производства'),
(324, 'Организация и технология испытаний'),
(325, 'Организация и технология отрасли'),
(326, 'Организация предпринимательской деятельности'),
(327, 'Организация производства'),
(328, 'Организация производства и менеджмент'),
(329, 'Организация производства на предприятии отрасли'),
(330, 'Организация производства на химическом предприятии'),
(331, 'Организация производства, маркетинг и менеджмент на предприятии'),
(332, 'Организация ЭВМ и систем'),
(333, 'Организация, нормирование и оплата труда'),
(334, 'Органическая химия'),
(335, 'Основы аудита'),
(336, 'Основы безопасности управления автомобилем'),
(337, 'Основы бизнес-планирования'),
(338, 'Основы биржевой торговли'),
(339, 'Основы гидравлики и гидропривода'),
(340, 'Основы графической информации'),
(341, 'Основы концептуального проектирования систем'),
(342, 'Основы логистики'),
(343, 'Основы менеджмента'),
(344, 'Основы менеджмента инженерно-технической службы'),
(345, 'Основы научных исследований'),
(346, 'Основы педагогики и психологии'),
(347, 'Основы программирования'),
(348, 'Основы проектирования web-приложений'),
(349, 'Основы проектирования и оборудование производств по переработке полимеров'),
(350, 'Основы проектирования и оборудование производства полимеров'),
(351, 'Основы проектирования и оборудования предприятий по переработке полимеров'),
(352, 'Основы проектирования и эксплуатации технологического оборудования'),
(353, 'Основы проектирования продукции'),
(354, 'Основы проектирования установок предприятий отрасли'),
(355, 'Основы путей сообщения (дороги)'),
(356, 'Основы САПР'),
(357, 'Основы САПР и программные статистические комплексы'),
(358, 'Основы сварочного производства'),
(359, 'Основы систем управления ресурсами предприятия'),
(360, 'Основы системного программного обеспечения'),
(361, 'Основы теории катализа'),
(362, 'Основы теории надёжности и диагностика'),
(363, 'Основы теории отраслевых рынков'),
(364, 'Основы теории управления'),
(365, 'Основы теории цифровых систем управления'),
(366, 'Основы теплотехнических измерений'),
(367, 'Основы термодинамики и кинетики синтеза ВМС'),
(368, 'Основы технической эксплуатации, обслуживания и ремонта'),
(369, 'Основы технического регулирования'),
(370, 'Основы технической эксплуатации автомобилей'),
(371, 'Основы технической эксплуатации. Обслуживание и ремонт'),
(372, 'Основы технологии машиностроения'),
(373, 'Основы технологии производства и ремонта'),
(374, 'Основы технологии производства и ремонта автомобилей'),
(375, 'Основы технологической эксплуатации автомобилей'),
(376, 'Основы трансляции'),
(377, 'Основы трудового права'),
(378, 'Основы управления разработкой программных систем'),
(379, 'Основы хозяйственного права'),
(380, 'Основы экономики и управления производством'),
(381, 'Основы электронной коммерции'),
(382, 'Отечественная история'),
(383, 'Оформление и представление магистерских диссертаций'),
(384, 'Оценка бизнеса'),
(385, 'Пакеты прикладных инженерных программ'),
(386, 'Патентоведение'),
(387, 'Педагогика и психология высшей школы'),
(388, 'Периферийные устройства'),
(389, 'Планирование и организация эксперимента (КР)'),
(390, 'Планирование на предприятии'),
(391, 'Планирование эксперимента'),
(392, 'Поверхностные явления и дисперсные системы'),
(393, 'Подъёмно-транспортное оборудование'),
(394, 'Политология'),
(395, 'Право'),
(396, 'Правоведение'),
(397, 'Правовое обеспечение отрасли'),
(398, 'Правовые основы'),
(399, 'Прикладная математическая статистика'),
(400, 'Прикладная механика'),
(401, 'Прикладная механика (детали машин)'),
(402, 'Прикладная механика (сопромат)'),
(403, 'Прикладная теория упругости'),
(404, 'Прикладная теория упругости и пластичности'),
(405, 'Прикладные научные исследования'),
(406, 'Применение математической физики'),
(407, 'Применение ЭВМ в химической технологии'),
(408, 'Программирование'),
(409, 'Программирование и основы алгоритмизации'),
(410, 'Программирование на языке высокого уровня. Алгоритмические языки'),
(411, 'Программное обеспечение систем управления'),
(412, 'Программное управление системами автоматизации'),
(413, 'Программные средства обработки экспериментальных данных'),
(414, 'Программные статистические комплексы'),
(415, 'Проектирование автоматизированных систем'),
(416, 'Проектирование авторемонтных предприятий'),
(417, 'Проектирование АСОИУ'),
(418, 'Проектирование единого информационного пространства виртуальных предприятий'),
(419, 'Проектирование и исследование специальных методов обработки'),
(420, 'Проектирование и разработка программного обеспечения'),
(421, 'Проектирование машиностроительных производств'),
(422, 'Проектирование предприятий автомобильного транспорта'),
(423, 'Проектирование предприятий химических  производств'),
(424, 'Проектирование производств и предприятий химической промышленности'),
(425, 'Проектирование систем автоматизации'),
(426, 'Проектирование человеко-машинного интерфейса'),
(427, 'Проектирование эксплуатационных предприятий'),
(428, 'Производственная логистика'),
(429, 'Производственная практика'),
(430, 'Производственная стратегия предприятия'),
(431, 'Производственно-техническая база автотранспортных предприятий'),
(432, 'Производственный менеджмент'),
(433, 'Промышленная экология'),
(434, 'Промышленные контроллеры'),
(435, 'Промышленные контроллеры и языки программирования МЭК'),
(436, 'Профессиональная этика'),
(437, 'Процессы и аппараты защиты окружающей среды'),
(438, 'Процессы и аппараты химических и пищевых производств'),
(439, 'Процессы и аппараты химической технологии'),
(440, 'Процессы и операции формообразования'),
(441, 'Процессы массопереноса в системах с участием твёрдой фазы'),
(442, 'Психология'),
(443, 'Психология и педагогика'),
(444, 'Психология профессиональной деятельности'),
(445, 'Пусковые качества ДВС. Топливные системы современных и перспективных ДВС'),
(446, 'Развитие и современное состояние автомобильного транспорта'),
(447, 'Развитие и современное состояние мировой автомобилизации'),
(448, 'Разработка управленческих решений'),
(449, 'Разработка эргономичных программных систем'),
(450, 'Распределённые компьютерные информационно-управляющие системы'),
(451, 'Расчёт и конструирование изделий и форм'),
(452, 'Расчёт и конструирование оборудования'),
(453, 'Расчёт, моделирование и конструирование оборудования с компьтерным управлением'),
(454, 'Региональная экономика'),
(455, 'Режущий инструмент'),
(456, 'Резание материалов'),
(457, 'Резины со специальными свойствами'),
(458, 'Реология материалов'),
(459, 'Реструктуризация предприятий'),
(460, 'Ресурсосберегающие технологии в промышленности'),
(461, 'Ресурсосбережение при проведении ТО и ремонта'),
(462, 'Рецептуростроение полимерных композиций'),
(463, 'Русский язык и культура речи'),
(464, 'Рынок ценных бумаг'),
(465, 'САПР технологических процессов'),
(466, 'САПР технологической оснастки'),
(467, 'Сертификация и лицензирование на транспорте'),
(468, 'Сетевые технологии'),
(469, 'Сети и телекоммуникации'),
(470, 'Сети ЭВМ и телекоммуникации'),
(471, 'Системное программное обеспечение'),
(472, 'Системный анализ'),
(473, 'Системный анализ процессов'),
(474, 'Системный анализ процессов химической технологии'),
(475, 'Системный анализ химико-технологических процессов'),
(476, 'Системы CAD'),
(477, 'Системы CAD/CAM/CAE'),
(478, 'Системы автоматизированного проектирования'),
(479, 'Системы визуального моделирования'),
(480, 'Системы искусственного интеллекта'),
(481, 'Системы качества'),
(482, 'Системы компьютерной математики'),
(483, 'Системы реального времени'),
(484, 'Системы управления знаниями'),
(485, 'Системы управления химико-технологическими процессами'),
(486, 'Ситуационный анализ'),
(487, 'Современные и перспективные электронные системы автомобилей'),
(488, 'Современные интернет-технологии'),
(489, 'Современные методы экономического анализа'),
(490, 'Современные проблемы инструментального обеспечения МП'),
(491, 'Современные проблемы науки в машиностроении'),
(492, 'Современные проблемы химической технологии'),
(493, 'Современные проблемы экономической науки'),
(494, 'Современные технологии управления'),
(495, 'Сопротивление материалов'),
(496, 'Социология'),
(497, 'Спецглавы математики'),
(498, 'Спецглавы общей и неорганической химии'),
(499, 'Спецглавы физики'),
(500, 'Специализиров подвижной состав'),
(501, 'Специальная технология'),
(502, 'Специальные процессы в ХТ, НХ и БТ'),
(503, 'Специальные процессы химической технологии'),
(504, 'Специальные технологии'),
(505, 'Спецификация, архитектура и проектирование программных систем'),
(506, 'Спецкурс по языкам программирования'),
(507, 'Справочно-правовые системы'),
(508, 'Средства автоматизации и управления'),
(509, 'Стабилизация эластомерных материалов'),
(510, 'Стандартизация при подготовке и оформлении научных документов'),
(511, 'Станки с ЧПУ и автоматические линии'),
(512, 'Статистика'),
(513, 'Статистическая обработка экспериментальных данных'),
(514, 'Статистические методы контроля и управления качеством'),
(515, 'Статистические методы контроля качества'),
(516, 'Стратегический маркетинг'),
(517, 'Стратегический менеджмент'),
(518, 'Страхование'),
(519, 'Страховое дело'),
(520, 'Структура и свойства полимерных композиций'),
(521, 'Теоретическая механика'),
(522, 'Теоретические и экспериментальные методы исследования в химии'),
(523, 'Теоретические основы автоматизированного управления'),
(524, 'Теоретические основы переработки полимеров'),
(525, 'Теоретические основы переработки термо- и реактопластов'),
(526, 'Теоретические основы переработки эластомеров'),
(527, 'Теоретические основы ресурсосбережения'),
(528, 'Теоретические основы технологических процессов'),
(529, 'Теоретические основы электротехники'),
(530, 'Теоретические основы энерго- и ресурсосбережения в химической технологии'),
(531, 'Теория автоматизированного управления'),
(532, 'Теория автоматического управления'),
(533, 'Теория вероятностей и массового обслуживания. Статистика'),
(534, 'Теория вероятностей и математическая статистика'),
(535, 'Теория вероятностей, математическая статистика и случайные процессы'),
(536, 'Теория и методика преподавания технических дисциплин'),
(537, 'Теория кризисного управления'),
(538, 'Теория менеджмента'),
(539, 'Теория механизмов и машин'),
(540, 'Теория организации и организационное поведение'),
(541, 'Теория отраслевых рынков'),
(542, 'Теория отраслевых рынков (продвинутый курс)'),
(543, 'Теория планирования эксперимента'),
(544, 'Теория принятия решений'),
(545, 'Теория систем'),
(546, 'Тепловые процессы'),
(547, 'Теплотехника'),
(548, 'Теплотехника и теплотехническое оборудование транспортных средств и автопредприятий'),
(549, 'Теплотехника и транспортная энергетика'),
(550, 'Термодинамика'),
(551, 'Тестирование и отладка программного обеспечения'),
(552, 'Техника транспорта и транспортные средства. Расчёт автомобилей'),
(553, 'Техника транспорта и транспортные средства'),
(554, 'Техника транспорта и транспортные средства. Теория автомобиля'),
(555, 'Техника эксперимента'),
(556, 'Техническая термодинамика и теплотехника'),
(557, 'Техническая физика и механика полимеров'),
(558, 'Техническая эксплуатация автомобилей'),
(559, 'Техническая эксплуатация автомобилей, оборудованных компьютерами и со встроенной диагностикой'),
(560, 'Техническая эксплуатация автомобилей, работающих на альтернативных видах топлива'),
(561, 'Техническая эксплуатация автомобилей. Текущий ремонт'),
(562, 'Технические измерения и приборы'),
(563, 'Технические средства автоматизации'),
(564, 'Технические средства автоматизации и управления'),
(565, 'Технические средства информационных систем'),
(566, 'Технический анализ и контроль производства'),
(567, 'Технологии программирования'),
(568, 'Технологическая оснастка'),
(569, 'Технологические измерения и производства'),
(570, 'Технологические методы обеспечения качества'),
(571, 'Технологические процессы автоматизированных проиводств'),
(572, 'Технологические процессы в машиностроении'),
(573, 'Технологические процессы и оборудование в промышленности'),
(574, 'Технологические процессы и производства'),
(575, 'Технологические процессы переработки пластмасс и эластомеров'),
(576, 'Технологические процессы специализированных производств'),
(577, 'Технологические процессы ТО, ремонта и диагностики автомобилей'),
(578, 'Технологические процессы химической промышленности'),
(579, 'Технологическое обеспечение качества'),
(580, 'Технологическое оборудование химической промышленности '),
(581, 'Технология восстановления деталей'),
(582, 'Технология восстановления деталей машин'),
(583, 'Технология изготовления изделий на основе эластомеров'),
(584, 'Технология изготовления режущего инструмента'),
(585, 'Технология командной разработки программных систем'),
(586, 'Технология конструкционных материалов'),
(587, 'Технология машиностроения'),
(588, 'Технология мономеров для ВМС'),
(589, 'Технология обработки на станках с ЧПУ'),
(590, 'Технология обработки неметаллических композиционных материалов'),
(591, 'Технология очистки и рекуперации промышленных выбросов'),
(592, 'Технология очистки и рекуперации промышленных отходов'),
(593, 'Технология переработки отходов в резиновой промышленности'),
(594, 'Технология переработки полимеров'),
(595, 'Технология разработки стандартов и нормативной документации'),
(596, 'Технология химических волокон'),
(597, 'Технология химических производств'),
(598, 'Технология шлифования'),
(599, 'Технология шлифования материалов'),
(600, 'Типы и структуры данных'),
(601, 'Товарные рынки'),
(602, 'Транспортное право'),
(603, 'Транспортно-эксплуатационные качества автомобильных дорог и городских улиц'),
(604, 'ТСА. Первичные преобразователи'),
(605, 'ТСА. Промышленные контролеры'),
(606, 'ТСА. Специальные исполнительные механизмы'),
(607, 'Управление в автоматизированном производстве'),
(608, 'Управление затратами'),
(609, 'Управление инновационными проектами'),
(610, 'Управление инновациями'),
(611, 'Управление капиталом предприятия'),
(612, 'Управление качеством'),
(613, 'Управление качеством на предприятии нефтегазохимического комплекса'),
(614, 'Управление качеством технологических процессов в промышленности'),
(615, 'Управление персоналом'),
(616, 'Управление производством'),
(617, 'Управление системами и процессами'),
(618, 'Управление снабжением и сбытом'),
(619, 'Управление стоимостью бизнеса'),
(620, 'Управление техническими системами'),
(621, 'Управление трудовыми ресурсами предприятий автомобильного транспорта'),
(622, 'Управление финансами'),
(623, 'Управление человеческими ресурсами'),
(624, 'Устройство и обслуживание автоматических климатических установок'),
(625, 'Учёт и анализ'),
(626, 'Физика'),
(627, 'Физика полимеров'),
(628, 'Физико-химические методы анализа'),
(629, 'Физико-химические основы переработки ВМС'),
(630, 'Физико-химия растворов полимеров'),
(631, 'Физическая культура'),
(632, 'Физическая органическая химия'),
(633, 'Физическая химия'),
(634, 'Физические основы измерений'),
(635, 'Физические основы измерений и эталоны'),
(636, 'Философия'),
(637, 'Философские вопросы естествознания и технических наук'),
(638, 'Философские проблемы науки и техники');
INSERT INTO `disciplines` (`id`, `name`) VALUES
(639, 'Финансовое планирование и бюджетирование'),
(640, 'Финансовый анализ'),
(641, 'Финансовый менеджмент'),
(642, 'Финансы'),
(643, 'Финансы и кредит'),
(644, 'Финансы предприятий'),
(645, 'Химико-технологические системы'),
(646, 'Химическая модификация полимерных материалов'),
(647, 'Химическая технология органических веществ'),
(648, 'Химические реакторы'),
(649, 'Химическое сопротивление материалов и защита от коррозии'),
(650, 'Химия'),
(651, 'Химия биополимеров'),
(652, 'Химия и технология полимерных покрытий'),
(653, 'Химия и технология полимеров'),
(654, 'Химия и физика полимеров'),
(655, 'Химия и физика полимеров со спец. свойствами'),
(656, 'Химия нефти'),
(657, 'Химия окружающей среды'),
(658, 'Химия пищи'),
(659, 'Химия полимеров'),
(660, 'Химия циклических и гетероциклических соединений'),
(661, 'Хозяйственное право'),
(662, 'Хранение и защита компьютерной информации'),
(663, 'Цифровая обработка сигнала'),
(664, 'Цифровые системы автоматизации и управления'),
(665, 'Частно-государственное партнёрство и социальная ответственность бизнеса'),
(666, 'Экологические основы качества'),
(667, 'Экологические проблемы автомобильного транспорта'),
(668, 'Экологические системы качества'),
(669, 'Экологический менеджмент и аудит'),
(670, 'Экология'),
(671, 'Эконометрика'),
(672, 'Эконометрика (продвинутый курс)'),
(673, 'Экономика'),
(674, 'Экономика автотранспортного предприятия'),
(675, 'Экономика и управление производством'),
(676, 'Экономика качества, стандартизации и сертификации'),
(677, 'Экономика машиностроительного производства'),
(678, 'Экономика недвижимости'),
(679, 'Экономика организации (предприятия)'),
(680, 'Экономика отраслевых рынков'),
(681, 'Экономика отраслей народного хозяйства'),
(682, 'Экономика отрасли'),
(683, 'Экономика предприятия'),
(684, 'Экономика программного обеспечения'),
(685, 'Экономика производства'),
(686, 'Экономика стандартизации, сертификации и качества'),
(687, 'Экономика стандартизации, сертификации, управления качеством'),
(688, 'Экономика труда'),
(689, 'Экономика химического предприятия'),
(690, 'Экономика химической отрасли'),
(691, 'Экономико-математические методы'),
(692, 'Экономико-математические методы и модели'),
(693, 'Экономическая оценка инвестиций'),
(694, 'Экономическая социология'),
(695, 'Экономическая теория'),
(696, 'Экономическая теория (макроэкономика)'),
(697, 'Экономические обоснования научных решений'),
(698, 'Экономический анализ'),
(699, 'Экономический анализ и управление производством'),
(700, 'Экономическое развитие'),
(701, 'Экспертные системы'),
(702, 'Эксплуатационные материалы '),
(703, 'Эксплуатация КИП и оборудование систем управления'),
(704, 'Электромеханические системы'),
(705, 'Электроника'),
(706, 'Электроника и электромеханические системы'),
(707, 'Электроника и электрооборудование транспортных и транспортно-технологических машин'),
(708, 'Электротехника'),
(709, 'Электротехника и промышленная электроника'),
(710, 'Электротехника и электроника'),
(711, 'Электрохимические и электрофизические методы обработки'),
(712, 'Явления переноса импульса и энергии в ХТ');

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
  `building` enum('A','B','D','V','S') NOT NULL COMMENT 'корпус (А, Б, Д, В, Спорткомплекс',
  `floor` tinyint(3) unsigned NOT NULL COMMENT 'этаж',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AVG_ROW_LENGTH=585 AUTO_INCREMENT=80 ;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `building`, `floor`) VALUES
(1, 'A-01', 'A', 0),
(2, 'A-03', 'A', 0),
(3, 'A-06', 'A', 0),
(4, 'A-08', 'A', 0),
(5, 'A-11', 'A', 1),
(6, 'A-12', 'A', 1),
(7, 'A-16', 'A', 1),
(8, 'A-18', 'A', 1),
(9, 'A-20', 'A', 2),
(10, 'A-25', 'A', 2),
(11, 'A-26', 'A', 2),
(12, 'A-29', 'A', 2),
(13, 'A-31', 'A', 3),
(14, 'A-32', 'A', 3),
(15, 'Б-102', 'B', 1),
(16, 'Б-103', 'B', 1),
(17, 'Б-104', 'B', 1),
(18, 'Б-108', 'B', 1),
(19, 'Б-110', 'B', 1),
(20, 'Б-201', 'B', 2),
(21, 'Б-202', 'B', 2),
(22, 'Б-203', 'B', 2),
(23, 'Б-205', 'B', 2),
(24, 'Б-206', 'B', 2),
(25, 'Б-207', 'B', 2),
(26, 'Б-208', 'B', 2),
(27, 'Б-209', 'B', 2),
(28, 'Б-210', 'B', 2),
(29, 'Б-300', 'B', 3),
(30, 'Б-301', 'B', 3),
(31, 'Б-302', 'B', 3),
(32, 'Б-303', 'B', 3),
(33, 'Б-304', 'B', 3),
(34, 'Б-305', 'B', 3),
(35, 'Б-306', 'B', 3),
(36, 'Б-307', 'B', 3),
(37, 'Б-308', 'B', 3),
(38, 'Б-309', 'B', 3),
(39, 'Б-401', 'B', 4),
(40, 'Б-402', 'B', 4),
(41, 'Б-403', 'B', 4),
(42, 'Б-404', 'B', 4),
(43, 'Б-405', 'B', 4),
(44, 'Б-406', 'B', 4),
(45, 'Б-408', 'B', 4),
(46, 'Б-002', 'B', 0),
(47, 'Б-003', 'B', 0),
(48, 'Б-004', 'B', 0),
(49, 'Б-006', 'B', 0),
(50, 'Б-008', 'B', 0),
(51, 'гараж № 3', 'B', 1),
(52, 'Б-410', 'B', 4),
(53, 'БЛК-11', 'B', 1),
(54, 'БЛК-15', 'B', 1),
(55, 'БЛК-31', 'B', 3),
(56, 'В-101', 'V', 1),
(57, 'В-105', 'V', 1),
(58, 'В-108', 'V', 1),
(59, 'В-109', 'V', 1),
(60, 'В-111', 'V', 1),
(61, 'В-201', 'V', 2),
(62, 'В-202', 'V', 2),
(63, 'В-203', 'V', 2),
(64, 'В-204', 'V', 2),
(65, 'В-206', 'V', 2),
(66, 'В-209', 'V', 2),
(67, 'В-211', 'V', 2),
(68, 'Д-101', 'D', 1),
(69, 'Д-102', 'D', 1),
(70, 'Д-106', 'D', 1),
(71, 'Д-116', 'D', 1),
(72, 'Д-201', 'D', 2),
(73, 'Д-202', 'D', 2),
(74, 'Д-207', 'D', 2),
(75, 'Д-218', 'D', 2),
(76, 'Д-221', 'D', 2),
(77, 'Д-222', 'D', 2),
(78, 'Спорткомплекс', 'S', 1),
(79, 'БЛК-17', 'B', 1);

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
  `type` enum('LECTURE','WORKSHOP','LAB','TUTORIAL','QUIZ','EXAMINATION') DEFAULT NULL COMMENT 'тип занятия (лекция, практика, лаба, консультация, зачёт, экзамен)',
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
