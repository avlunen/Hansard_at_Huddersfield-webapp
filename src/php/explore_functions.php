<?php

session_start();

include_once './db/query_handler.php';
include_once 'convert_data.php';

header('Content-Type: text/html;charset=utf-8');

if (!array_key_exists('id', $_REQUEST)) $id = 1;
else $id = $_REQUEST['id'];

array_map('unlink', glob("../../tmp/" . session_id() . "*"));

if (!isset($_SESSION['PID'])) {
  $_SESSION["PID"] =  query_handler::gettingPID("dbname=hansard");
} else {
  $_SESSION["previous_PID"] = $_SESSION["PID"];
  $_SESSION["PID"] =  query_handler::gettingPID("dbname=hansard");
}

if (isset($_GET['action'])) {

  $house = strtolower($_GET['house']);

  if ($_GET['action'] == "contribution") {

    if ($_GET['sort'] == "date") {
      $sort = "sittingday";
    } else {
      $sort = $_GET['sort'];
    }

    if ($house != "both") {

      $sql =
        "SELECT count(*)"
        . "FROM hansard_" . $house . "." . $house . ", to_tsquery('simple','" . $_GET['word'] . "') as q "
        . "WHERE "
        . "sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and idxfti_simple @@ q ";

      $total = query_handler::query_no_parameters($sql, "dbname=hansard");

      $sql =
        "SELECT id, sittingday, contributiontext, member, ts_rank(idxfti_simple, q) AS relevance "
        . "FROM hansard_" . $house . "." . $house . ", to_tsquery('simple','" . $_GET['word'] . "') as q "
        . "WHERE "
        . "sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and idxfti_simple @@ q "
        . "ORDER BY " . $sort . " " . $_GET['order'] . " "
        . "LIMIT " . $_GET['limit'] . " "
        . "OFFSET " . $_GET['offset'];
    } else {

      $limit = $_GET['limit'] / 2;
      $offset = $_GET['offset'] / 2;

      $sql =
        "SELECT count(*) "
        . "FROM "
        . "("
        . "SELECT id"
        . " FROM hansard_commons.commons, to_tsquery('simple','" . $_GET['word'] . "') as q "
        . " WHERE "
        . "hansard_commons.commons.sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and hansard_commons.commons.idxfti_simple @@ q "
        . "UNION ALL "
        . "SELECT id"
        . " FROM hansard_lords.lords, to_tsquery('simple','" . $_GET['word'] . "') as q "
        . " WHERE "
        . "hansard_lords.lords.sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and hansard_lords.lords.idxfti_simple @@ q "
        . ") x";

      $total = query_handler::query_no_parameters($sql, "dbname=hansard");

      $sql =
        "(SELECT id, sittingday, contributiontext, member, ts_rank(idxfti_simple, q) AS relevance, 'Commons' AS source "
        . "FROM hansard_commons.commons, to_tsquery('simple','" . $_GET['word'] . "') as q "
        . "WHERE "
        . "hansard_commons.commons.sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and hansard_commons.commons.idxfti_simple @@ q "
        . "ORDER BY " . $sort . " " . $_GET['order'] . " "
        . "LIMIT " . $limit . " "
        . "OFFSET " . $offset . ") "
        . "UNION ALL "
        . "(SELECT id, sittingday, contributiontext, member, ts_rank(idxfti_simple, q) AS relevance, 'Lords' AS source "
        . "FROM hansard_lords.lords, to_tsquery('simple','" . $_GET['word'] . "') as q "
        . "WHERE "
        . "hansard_lords.lords.sittingday BETWEEN '" . $_GET['year'] . "-01-01'::DATE AND '" . $_GET['year'] . "-12-31'::DATE "
        . "and hansard_lords.lords.idxfti_simple @@ q "
        . "ORDER BY " . $sort . " " . $_GET['order'] . " "
        . "LIMIT " . $limit . " "
        . "OFFSET " . $offset . ")";
    }

    $rows = query_handler::query_no_parameters($sql, "dbname=hansard");

    $var = convert_data::gen_json_documents($rows, $_GET['word'], $total);
    $var2 = json_encode($var);
    echo $var2;
  }
} else {

  if (isset($_POST['house'])) {
    $house = strtolower($_POST['house']);
  }


  if ($_POST['action'] == "wordcloud") {

    $stopwords_list = " '§', '#', '|', 'hon', 'mr', 'sect', 'x2014', 'government', 'right', 'house', 'member', 'gentleman', 'bill', 'friend', 'minister', 'members', 'question', 'secretary', 'committee', 'x00a3', '0', 'sir', 'amendment', 'lord', 'clause', 'prime', 'parliament', 'noble', 'office', 'speaker', 'proposed', 'learned', 'chancellor', 'motion', 'beg', 'majesty', 'exchequer', '000l', 'chief', 'gentlemen', 'ministry', 'commissioners', 'baronet', 'honourable', 'ministers', 'department', 'colonel', 'constituency', 'gent', 'amendments', 'lords', 'attorney', 'paper', 'lieutenant', 'x0021', 'lieut', 'mrs', 'bishops', 'duke', 'bills', 'bishop', 'commons', 'marquis', 'x2013', 'x00e9', 'buonapart', 'clarke', 'moved', 'wellesley', 'highness', 'melville', 'castlereagh', 'oliver', 'wellington', 'rose', 'lordships', 'earl', 'act', 'baroness', 'debate', 'viscount', 'marquess', 'lady', 'peers', 'royal', 'king', 'queen', 'pergami', 'bergami', 'brougham', 'gordon', 'reverend', 'governor', 'russell', 'lordship', 'chamber', 'kimberley', 'baron', 'acts', 'edmunds', 'normanby', 'canning', 'moore', 'john', 'eldon', 'grenville', 'hawkesbury', 'gambier', 'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 'theirs', 'themselves', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'having', 'do', 'does', 'did', 'doing', 'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should', 'now', 'hon:', 'mr:', 'sect:', 'hon', 'mr', 'sect', 'hon.', 'mr.', 'sect.', 'x2014', 'government', 'right', 'house', 'member', 'gentleman', 'bill', 'friend', 'minister', 'members', 'question', 'secretary', 'committee', 'sir', 'amendment', 'lord', 'clause', 'prime', 'parliament', 'noble', 'office', 'speaker', 'proposed', 'learned', 'chancellor', 'motion', 'beg', 'majesty', 'exchequer',  'chief', 'gentlemen', 'ministry', 'commissioners', 'baronet', 'honourable', 'ministers', 'department', 'colonel', 'constituency', 'gent', 'amendments', 'lords', 'attorney', 'paper', 'lieutenant',  'lieut', 'mrs', 'bishops', 'duke', 'bills', 'bishop', 'commons', 'marquis', 'x2013', 'x00e9', 'buonapart', 'clarke', 'moved', 'wellesley', 'highness', 'melville', 'castlereagh', 'oliver', 'wellington', 'rose', 'lordships', 'earl', 'act', 'baroness', 'debate', 'viscount', 'marquess', 'lady', 'peers', 'royal', 'king', 'queen', 'pergami', 'bergami', 'brougham', 'gordon', 'reverend', 'governor', 'russell', 'lordship', 'chamber', 'kimberley', 'baron', 'acts', 'edmunds', 'normanby', 'canning', 'moore', 'john', 'eldon', 'grenville', 'hawkesbury', 'gambier', 'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 'theirs', 'themselves', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'having', 'do', 'does', 'did', 'doing', 'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should', 'now', 'hon:', 'mr:', 'sect:', 'hon', 'mr', 'sect', 'hon.', 'mr.', 'sect.', 'x2014', 'government', 'right', 'house', 'member', 'gentleman', 'bill', 'friend', 'minister', 'members', 'question', 'secretary', 'committee', 'sir', 'amendment', 'lord', 'clause', 'prime', 'parliament', 'noble', 'office', 'speaker', 'proposed', 'learned', 'chancellor', 'motion', 'beg', 'majesty', 'exchequer',  'chief', 'gentlemen', 'ministry', 'commissioners', 'baronet', 'honourable', 'ministers', 'department', 'colonel', 'constituency', 'gent', 'amendments', 'lords', 'attorney', 'paper', 'lieutenant',  'lieut', 'mrs', 'bishops', 'duke', 'bills', 'bishop', 'commons', 'marquis', 'x2013', 'x00e9', 'buonapart', 'clarke', 'moved', 'wellesley', 'highness', 'melville', 'castlereagh', 'oliver', 'wellington', 'rose', 'lordships', 'earl', 'act', 'baroness', 'debate', 'viscount', 'marquess', 'lady', 'peers', 'royal', 'king', 'queen', 'pergami', 'bergami', 'brougham', 'gordon', 'reverend', 'governor', 'russell', 'lordship', 'chamber', 'kimberley', 'baron', 'acts', 'edmunds', 'normanby', 'canning', 'moore', 'john', 'eldon', 'grenville', 'hawkesbury', 'gambier', 'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 'theirs', 'themselves', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'having', 'do', 'does', 'did', 'doing', 'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should', 'now', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'rt', 'ought', 'should', 'would', 'could', 'might', 'may', 'shall', 'will' ";

    $year_start = $_POST["params"]["year"][0];
    $year_end = $_POST["params"]["year"][1];

    $years = "";
    $where_clause = "";
    $vars_clause = "";


    for ($x = $year_start; $x <= $year_end; $x++) {
      if ($years == "") {
        $years = "'" . $x . "'";
      } else {
        $years .= ", '" . $x . "'";
      }
    }

    $SQL_clause = "";


    if ($house != "both") {


      $sql =
        "SELECT word, sum(hits) as freq " .
        "FROM ( " .
        "SELECT word, hits " .
        "from hansard_" . $house . "_single_word_year_500 " .
        "where year in (" . $years . ") " .
        "and word not in (" . $stopwords_list . ")" .
        ") x group by word order by freq desc limit 500";
    } else {


      $sql =
        "SELECT word, sum(hits) as freq " .
        "FROM ( " .
        "SELECT word, hits " .
        "from hansard_commons_single_word_year_500 " .
        "where year in (" . $years . ") " .
        "and word not in (" . $stopwords_list . ")" .
        "UNION ALL " .
        "SELECT word, hits " .
        "from hansard_lords_single_word_year_500 " .
        "where year in (" . $years . ") " .
        "and word not in (" . $stopwords_list . ")" .
        ") x group by word order by freq desc limit 500";
    }


    $rows = query_handler::query_no_parameters($sql, "dbname=hansard");

    $var = convert_data::gen_json_word_cloud($rows);
    $var2 = json_encode($var);
    echo $var2;
  } else if ($_POST['action'] == "multiple_line_chart") {

    $i = 0;
    foreach ($_POST['parameters'] as &$value) {

      $cleaned_term = strtolower(convert_data::clean_query($value["term"]));
      $num_words = sizeof(explode(" ", $cleaned_term));

      if ($house != "both") {

        $sql =

          "select frequency, x.year as myear, total from (" .
          "select sum(hits) as frequency, year " .
          "from hansard_" . $house . "_single_word_year " .
          "where word like '" . $cleaned_term . "' " .
          "AND year BETWEEN '" . $_POST['dateFrom'] . "' AND '" . $_POST['dateTo'] . "' " .
          "group by year " .
          "order by year ) x " .
          "JOIN (select year, total from hansard_" . $house . "_total_word_year) as y ON y.year = x.year " .
          "order by x.year asc";
      } else {

        $sql =
          "SELECT sum(frequency) as frequency, total as 0, myear " .
          "FROM ( " .
          "select sum(hits) as frequency, year as myear, total " .
          "from hansard_commons_single_word_year " .
          "where word like '" . $cleaned_term . "' " .
          "AND year BETWEEN '" . $_POST['dateFrom'] . "' AND '" . $_POST['dateTo'] . "' " .
          "group by year " .
          "UNION ALL " .
          "select sum(hits) as frequency, year as myear " .
          "from hansard_lords_single_word_year " .
          "where word like '" . $cleaned_term . "' " .
          "AND year BETWEEN '" . $_POST['dateFrom'] . "' AND '" . $_POST['dateTo'] . "' " .
          "group by year " .
          ") x group by myear ORDER BY myear";
      }

      $rows[$i] = query_handler::query_no_parameters($sql, "dbname=hansard");
      $i++;
    }

    if ($house == "both") {

      $sql_extra =
        "SELECT sum(total) as total, year " .
        "FROM " .
        "( " .
        "select total, year from hansard_commons_total_word_year where year BETWEEN '" . $_POST['dateFrom'] . "' AND '" . $_POST['dateTo'] . "' " .
        "UNION ALL " .
        "select total, year from hansard_lords_total_word_year where year BETWEEN '" . $_POST['dateFrom'] . "' AND '" . $_POST['dateTo'] . "' " .
        ") " .
        "x " .
        "group by year ORDER BY year";

      $total_both = query_handler::query_no_parameters($sql_extra, "dbname=hansard");
    } else {
      $total_both = "";
    }

    $var = convert_data::gen_json_line($rows, $total_both, $_POST['parameters'], $_POST['dateFrom'], $_POST['dateTo']);

    $var2 = json_encode($var);
    echo $var2;
  } else if ($_POST['action'] == "bubble") {

    $parameters = $_POST['params'];


    if ($parameters['comparisonCorpus']['preCalculated'][0] == "false") {

      if ($parameters['comparisonCorpus']['term'] != "") {
        $cleaned_term = convert_data::clean_query($parameters['comparisonCorpus']['term']);
        $ts_term = convert_data::gen_postgresql_query($cleaned_term);
        $sql_term_1 = ", to_tsquery('simple','" . $ts_term . "') as q ";
        $sql_term_2 = "and idxfti_simple @@ q ";
      } else {
        $sql_term_1 = "";
        $sql_term_2 = "";
      }

      if ($parameters['comparisonCorpus']['member'] != "") {
        $sql_member = "and member = '" . $parameters['comparisonCorpus']['member'] . "'";
      } else {
        $sql_member = "";
      }

      $house = $parameters['comparisonCorpus']['house'];
      $dateFrom = $parameters['comparisonCorpus']['dateFrom'];
      $dateTo = $parameters['comparisonCorpus']['dateTo'];

      $sql =
        "SELECT contributiontext "
        . "FROM hansard_" . $house . "." . $house . $sql_term_1 . " "
        . "WHERE "
        . "sittingday BETWEEN '" . $dateFrom . "'::DATE AND '" . $dateTo . "'::DATE "
        . $sql_member
        . $sql_term_2;

      $rows = query_handler::query_no_parameters($sql, "dbname=hansard");
      $comparison_path = convert_data::gen_kw_documents($rows,  session_id() . "_comparison");
      $pre_calculated_data_comparison = "null";
    } else {

      $type = $parameters['comparisonCorpus']['preCalculated'][0];
      $house = $parameters['comparisonCorpus']['house'];
      $file = $parameters['comparisonCorpus']['preCalculated'][1] . "_" . $house . ".Rda";

      $pre_calculated_data_comparison = "/data/web/R_data/" . $type . "/" . $file;
    }

    if ($parameters['targetCorpus']['preCalculated'][0] == "false") {

      if ($parameters['targetCorpus']['term'] != "") {
        $cleaned_term = convert_data::clean_query($parameters['targetCorpus']['term']);
        $ts_term = convert_data::gen_postgresql_query($cleaned_term);
        $sql_term_1 = ", to_tsquery('simple','" . $ts_term . "') as q ";
        $sql_term_2 = "and idxfti_simple @@ q ";
      } else {
        $sql_term_1 = "";
        $sql_term_2 = "";
      }

      if ($parameters['targetCorpus']['member'] != "") {
        $sql_member = "and member = '" . $parameters['targetCorpus']['member'] . "'";
      } else {
        $sql_member = "";
      }

      $house = $parameters['targetCorpus']['house'];
      $dateFrom = $parameters['targetCorpus']['dateFrom'];
      $dateTo = $parameters['targetCorpus']['dateTo'];

      $sql =
        "SELECT contributiontext "
        . "FROM hansard_" . $house . "." . $house . $sql_term_1 . " "
        . "WHERE "
        . "sittingday BETWEEN '" . $dateFrom . "'::DATE AND '" . $dateTo . "'::DATE "
        . $sql_member
        . $sql_term_2;

      $rows = query_handler::query_no_parameters($sql, "dbname=hansard");
      $target_path = convert_data::gen_kw_documents($rows, session_id() . "_target");
      $pre_calculated_data_target = "null";
    } else {
      $type = $parameters['targetCorpus']['preCalculated'][0];
      $house = $parameters['targetCorpus']['house'];
      $file = $parameters['targetCorpus']['preCalculated'][1] . "_" . $house . ".Rda";

      $pre_calculated_data_target = "/data/web/R_data/" . $type . "/" . $file;
    }


    //$exec = shell_exec("C:\\R-3.6.2\\bin\\Rscript.exe ..\\R\\keywords.r " . session_id() . " " . $pre_calculated_data_comparison . " " . $pre_calculated_data_target);
    $_SESSION["R_PID"] = shell_exec("Rscript --no-save --no-restore --verbose ../R/keywords.r " . session_id() . " " . $pre_calculated_data_comparison . " " . $pre_calculated_data_target) . " & echo \$!";


    if (file_exists("../../tmp/" . session_id() . "_kw.csv")) {
      echo session_id() . "_kw.csv";
    } else {
      echo false;
    }

    #echo session_id() . " " . $pre_calculated_data_comparison . " " . $pre_calculated_data_target;
  } else if ($_POST['action'] == "killBubble") {
    $var2 = shell_exec("pkill -f /usr/lib/R/bin/exec/R");
    echo $var2;
  }
}
