

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<?
    // todo: преобразовать в объектно-ориентированный формат

class DisciplineMatcher
{
    function GetMatch($_subjects, $_short)
    {
		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');
		/**
		 * Выделение слов в сокрщанеии $_short в масив $shortWords
		 * Удаляем последнюю точку, так как из-за неё неправильно составляется массив слов
		 */
		$short = mb_convert_case(rtrim($_short), MB_CASE_LOWER, 'UTF-8');
		$shortWords = mb_split("[,.\\- ]+", $short);
		if ($shortWords[count($shortWords)-1] == '')
		{
			unset($shortWords[count($shortWords)-1]);
		}
		foreach ($_subjects as $_key => $_subject)
		{
			/**
			 * Выделение слов в названии текущего предмета в массив $subjectWords
			 */
			$subject = mb_convert_case($_subject, MB_CASE_LOWER, 'UTF-8');
			$subjectWords = mb_split("[,.\\- ]+", $subject);
			/**
			 * Создание аббревиатур
			 * Закомментировано за ненадобностью - теперь абревиатуры ищутся в следующем пункте
			 */
			/* {
			  $abbreviation = "";
			  foreach ($subjectWords as $subjectWord)
			  {
			  if (mb_strlen($subjectWord)>=3)
			  {
			  $abbreviation .= mb_substr($subjectWord, 0, 1);
			  }
			  }
			  //echo $abbreviation, " ", $short, "<BR>\n";
			  if ($abbreviation === $short)
			  {
			  return $_key;
			  }
			  } */
			/**
			 * Поиск сокращений. Сравнение идет попарно между словами в $short и $subject.
			 * Считаем что слова совпадают если слово из $subject начинается со слова из $short.
			 */
			{
				$subjectWordsCount = count($subjectWords);
				$subjectAbbreviations = array();
				$subjectAbbreviations[] = $subjectWords;
				/*
				 * Данный кусок кода выполняет составление всевозможных абревиатур, например
				 * из 'Моделирование технологии процесса синтеза ВМС' составляется массив
				 * array('мт процесса синтеза ВМС'], 'мтп синтеза ВМС', 'мтпс ВМС' ...) и т.д.
				 */
				for ($w = 0; $w < $subjectWordsCount; $w++)
				{
					$currentAbbreviation = array();
					for ($i = 0; $i < $w; $i++)
					{
						$currentAbbreviation[] = $subjectWords[$i];
					}
					$currentAbbreviationBackup = $currentAbbreviation;
					for ($abbrLettersCount = 1; $abbrLettersCount < $subjectWordsCount - $w; $abbrLettersCount++)
					{
						$currentAbbreviation = $currentAbbreviationBackup;
						$abbrWord = "";
						for ($w2 = $w; $w2 < $w + $abbrLettersCount + 1; $w2++)
						{
							$abbrWord .= mb_substr($subjectWords[$w2], 0, 1, 'UTF-8');
						}
						if (!empty($abbrWord))
						{
							$currentAbbreviation[] = $abbrWord;
						}
						for ($i2 = $w + $abbrLettersCount + 1; $i2 < $subjectWordsCount; $i2++)
						{
							$currentAbbreviation[] = $subjectWords[$i2];
						}
						$subjectAbbreviations[] = $currentAbbreviation;
					}
				}
				/*
				 * Здесь выполняется сравнение всех аббревиатур с текущим сокращением, поиск
				 * которого производится
				 */
				$abbreviationsCount = count($subjectAbbreviations);
				for ($q = 0; $q < $abbreviationsCount; $q++)
				{
					$subjectWordsInner = $subjectAbbreviations[$q];
					$keepTry = (count($subjectWordsInner) == count($shortWords));
					for ($i = 0; $i < $subjectWordsCount && $keepTry; $i++)
					{
						if (!empty($shortWords[$i]))
						{
							$position = mb_strpos($subjectWordsInner[$i], $shortWords[$i], 0, 'UTF-8');
							if (($position === false) || !($position === 0))
							{
								$keepTry = false;
							}
						}
						else
						{
							$keepTry = false;
						}
						if ($keepTry === true)
						{
							return $_key;
						}
					}
				}
			}
			/**
			 * Отлавливаем креатив когда в названии предмета после каждой буквы ставится пробел
			 * по типу 'О р г а н и з а ц и я и т е х н о л о г и я о т р а с л и'.
			 * Будем считать что в таком случае название предмета пишется полностью, поэтому
			 * сливаем все буквы из $short в одно большое слово, так же слова из $subject сливаем
			 * в одно слово и сравниваем их.
			 */
			{
				$wordSubject = implode('', $subjectWords);
				$wordShort = implode('', $shortWords);
				if (!empty($wordShort))
				{
					$position = mb_strpos($wordSubject, $wordShort, 0, 'UTF-8');
					if ($position === 0)
					{
						return $_key;
					}
				}
				else
				{
					return null;
				}
			}
		}
		return null;
    }
}
?>