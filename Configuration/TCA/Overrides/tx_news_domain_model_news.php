<?php

use GeorgRinger\Faker\Property\RealText;

$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['title']['faker'] = RealText::getSettings(['maxNbChars' => 60]);
$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['teaser']['faker'] = RealText::getSettings(['maxNbChars' => 300]);
$GLOBALS['TCA']['tx_news_domain_model_news']['columns']['bodytext']['faker'] = RealText::getSettings(['maxNbChars' => 1000]);
