<?

class DisciplineMatcher
{
    function GetMatch($_subjects, $_short, &$_algorithm_part=null, &$_debug_info=null)
    {
        $_algorithm_part = null;
        $_debug_info = null;
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');

        $short = mb_convert_case(rtrim($_short), MB_CASE_LOWER, 'UTF-8');

        foreach ($_subjects as $_key => $_subject)
        {
            $subject = mb_convert_case($_subject, MB_CASE_LOWER, 'UTF-8');
            if ($short == $subject)
            {
                $_algorithm_part = 'Полное совпадение';
                $_debug_info = array('short'=>$short, 'original'=>$subject);
                return $_key;
            }
        }

        /**
         * Выделение слов в сокрщанеии $_short в масив $shortWords
         * Удаляем последнюю точку, так как из-за неё неправильно составляется массив слов
         */
        $shortWords = mb_split("[,.\\- ]+", $short);
        if ($shortWords[count($shortWords)-1] == '')
        {
            unset($shortWords[count($shortWords)-1]);
        }
        $subject = '';
        $subjectWords = array();
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
                    for ($i = 0; ($i < count($subjectWordsInner)) && $keepTry; $i++)
                    {
                        if (!empty($shortWords[$i]) && !empty($subjectWordsInner[$i]))
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
                    }
                    if ($keepTry === true)
                    {
                        $_debug_info = array(
                            'q'=>$q,
                            'subjectWordsInner'=>$subjectWordsInner,
                            'shortWords'=>$shortWords
                        );
                        if ($q === 0)
                        {
                            $_algorithm_part = 'Пословное сравнение с оригиналом';
                        }
                        else
                        {
                            $_algorithm_part = 'Сравнение с аббревиатурами';
                        }
                        return $_key;
                    }
                }
            }
        }

        foreach ($_subjects as $_key => $_subject)
        {
            $subject = mb_convert_case($_subject, MB_CASE_LOWER, 'UTF-8');
            $subjectWords = mb_split("[,.\\- ]+", $subject);
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
                    //$position = mb_strpos($wordSubject, $wordShort, 0, 'UTF-8');
                    if ($wordSubject === $wordShort)
                    {
                        $_algorithm_part = 'Использование пробелов для позиционирования';
                        $_debug_info = array('wordSubject'=>$wordSubject, 'wordShort'=>$wordShort);
                        return $_key;
                    }
                }
                else
                {
                    return null;
                }
            }
        }

        foreach ($_subjects as $_key => $_subject)
        {
            $subject = mb_convert_case($_subject, MB_CASE_LOWER, 'UTF-8');
            $subjectWords = mb_split("[,.\\- ]+", $subject);
            $short2 = mb_convert_case(rtrim($_short), MB_CASE_LOWER, 'UTF-8');
            $short2 = mb_split("[,. ]+", $short2);
            if ($short2[count($short2)-1] == '')
            {
                unset($short2[count($short2)-1]);
            }

            /**
             * Теперь считаем, что дефис это не разделитель двух слов, а сокращение, и значит что он
             * равносилен регулярному выражению m!.*! из Perl
             * В случае если в слове попадается дефис, то из частей разделенных дефисом строим
             * регулярное выражение m/^1.*2$/, и проверяем, совпало ли слово. Если дефиса не найдено,
             * то просто проверяем на совпадение сокращения с началом оригинального слова.
             * При удачном совпадении всех слов, сокращение считается опознанным и возвращается его
             * индекс.
             */
            {
                if (count($short2) == count($subjectWords))
                {
                    $needToContinue = true;
                    $regexes = array();
                    for ($i=0; $i<count($short2) && $needToContinue; $i++)
                    {
                        $position = mb_strpos($short2[$i], '-', 0, 'UTF-8');
                        if ($position === false)
                        {
                            $position = mb_strpos($subjectWords[$i], $short2[$i], 0, 'UTF-8');
                            if (($position === false) || !($position === 0))
                            {
                                $needToContinue = false;
                            }
                            $regexes[] = $short2[$i];
                        }
                        else
                        {
                            $parts = mb_split("\\-", $short2[$i]);
                            $regex = '/^'.$parts[0].'.*'.$parts[1].'$/';
                            $regexes[] = $regex;
                            $result = preg_match($regex, $subjectWords[$i]);
                            if ($result !== 1)
                            {
                                $needToContinue = false;
                            }
                        }
                    }
                    if ($needToContinue === true)
                    {
                        $_algorithm_part = 'Поиск сокращений через тире';
                        $_debug_info = array('short2'=>$short2, 'subjectWords'=>$subjectWords, 'regexes'=>$regexes);
                        return $_key;
                    }
                }
            }
        }

        return null;
    }
    
    function GetDebugOutput($subjects, $short)
    {
        echo '<BR><BR>';
        $index = GetMatch($subjects, $short, $algorithm, $debugInfo);
        echo 'Информация для отладки алгоритма:<BR>';
        echo 'Сокращение: '.$short.'<BR>';
        echo 'Алгоритм: '.$algorithm.'<BR>';
        echo 'Отладочная информация:<BR>';
        //print_r($debugInfo);
        var_dump($debugInfo);
        echo '<BR>Конец отладочной информации';
    }
}
?>