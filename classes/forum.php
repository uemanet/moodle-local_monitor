<?php

/**
 * Class local_wsintegracao_forum
 * @copyright   2017 Uemanet
 * @author      Uemanet
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_monitor_forum extends external_api
{
    /**
    * Returns description of get_tutor_forum_answers parameters
    * @return external_function_parameters
    */
    public static function get_tutor_forum_answers_parameters()
    {
        return new external_function_parameters(array(
            'pes_id' => new external_value(PARAM_INT, 'id do tutor do lado do Harpia'),
            'trm_id' => new external_value(PARAM_INT, 'id de turma do lado do Harpia')
        ));
    }

    /**
    * Returns forum tutor answers
    * @param $pes_id
    * @param $trm_id
    */

    public static function get_tutor_forum_answers($pes_id, $trm_id)
    {
        global $DB;


        self::validate_parameters(self::get_tutor_forum_answers_parameters(), array(
            'pes_id' => $pes_id,
            'trm_id' => $trm_id
        ));

        $userdata =  $DB->get_record('int_tutor_group', array('pes_id' => $pes_id), '*');
        $userId  = $userdata->userid;

        if(!$userId) {
            throw new Exception("O tutor de pes_id: ".$pes_id ." não está mapeado no ambiente virtual.");
        }

        $datacourse =  $DB->get_record('int_turma_course', array('trm_id'=>$trm_id), '*');
        $courseId  = $datacourse->courseid;
        if(!$courseId) {
            throw new Exception("A turma com id: ".$trm_id ." não está mapeada com o ambiente virtual.");
        }

        $course =  $DB->get_record('course', array('id'=>$courseId), '*');

        $returnData = [];

        $parameters = array(
          $userId,
          $courseId
        );

        $returnData['id'] = $userId;
        $returnData['course'] = $course->fullname;
        $returnData['itens'] = [];
        // recebe todas as discussions para esse determinado curso
        $query = "SELECT {forum_discussions}.*, {groups}.id as groupid ,{groups}.name as groupname FROM {forum_discussions}
                  INNER JOIN {groups} ON {groups}.id = {forum_discussions}.groupid
                  WHERE userid = ? and course = ? ORDER BY groupname, {forum_discussions}.name";
        $discussions = $DB->get_records_sql($query, $parameters);

        foreach ($discussions as $key => $discussion) {
          $posts = self::make_tree_of_discussions($discussion->id, $userId);

          $poststutor_answered = 0;
          $posts_students = 0;
          $primeiro = array_shift($posts['posts']);
          $numerador = 0;
          $denominador = 0;

          foreach ($primeiro->children as $key => $post) {

              if ($post->userid != $userId) {

                $posts_students++;
                foreach ($post->children as $value) {

                  if ($value->userid == $userId) {
                    $numerador = $numerador + $value->created - $post->created;
                    $denominador++;
                    $poststutor_answered++;
                    break;
                  }
                }

              }

          }

          $media = $numerador/$denominador;
          $dias = floor($media/(3600*24));
          $horas = floor(($media-($dias*3600*24))/3600);
          $minutos = floor(($media-($horas*3600)-($dias*3600*24))/60);
          $segundos = floor($media%60);
          if (!is_nan($media)) {
            $tempo =  $dias . 'd' .$horas . "h" . $minutos . "min" ;
          }else {
            $tempo = '';
          }

          $returnData['itens'][] = array(
                                         'idgrupo' => $discussion->groupid,
                                         'grupo' => $discussion->groupname,
                                         'discussion' => $discussion->name,
                                         'postsstudents' => $posts_students,
                                         'poststutor' => $poststutor_answered,
                                         'participacaototal' => $posts['participacaototal'],
                                         'percentual' => number_format ($poststutor_answered/$posts_students, 2),
                                         'tempo' => $tempo
                                       );
        }

        return $returnData;
    }

    public static function make_tree_of_discussions($discussionId, $userId)
    {
      global $DB;

      $parameters = array(
          (int)$discussionId,
          $userId
      );

      $posts = $DB->get_records_sql("SELECT id, parent, userid, created FROM {forum_posts}  WHERE discussion = ?", $parameters);
      $postsTutor = count($DB->get_records_sql("SELECT id, parent, userid FROM {forum_posts}  WHERE discussion = ? AND parent != 0 AND userid = ?", $parameters));
      $postsStudents = count($DB->get_records_sql("SELECT id, parent, userid FROM {forum_posts}  WHERE discussion = ? and userid != ?", $parameters));


      foreach ($posts as $pid=>$p) {
          if (!$p->parent) {
              continue;
          }
          if (!isset($posts[$p->parent])) {
              continue;
          }
          if (!isset($posts[$p->parent]->children)) {
              $posts[$p->parent]->children = array();
          }
          $posts[$p->parent]->children[$pid] =& $posts[$pid];
      }

      return $returnData = [
        'posts' => $posts,
        'participacaototal' => number_format ($postsTutor/$postsStudents, 2)
      ];

    }

    /**
    * Returns description of get_tutor_forum_answers return values
    * @return external_function_parameters
     */
    public static function get_tutor_forum_answers_returns()
    {
        return new external_function_parameters(array(
            'id' => new external_value(PARAM_INT, 'Id do tutor'),
            'course' =>  new external_value(PARAM_TEXT, 'Nome completo do curso que o tutor está vinculado'),
            'itens' => new external_multiple_structure(
                        new external_single_structure(array(
                            'idgrupo' => new external_value(PARAM_TEXT, 'ID de Grupo da discussion.'),
                            'grupo' => new external_value(PARAM_TEXT, 'Grupo da discussion.'),
                            'discussion' => new external_value(PARAM_TEXT, 'Nome da discussion ao qual o tutor está vinculado.'),
                            'poststutor' => new external_value(PARAM_TEXT, 'Quantidade de posts que o tutor fez em uma discussion'),
                            'postsstudents' => new external_value(PARAM_TEXT, 'Quantidade de posts feitos pelos alunos em uma discussion'),
                            'percentual' => new external_value(PARAM_TEXT, 'Percentual de respostas de um tutor em uma discussion'),
                            'participacaototal' => new external_value(PARAM_TEXT, 'Participação completa do tutor em uma discussion'),
                            'tempo' => new external_value(PARAM_TEXT, 'Tempo médio de respostas aos fóruns')
                        )
                    )
                )
            )
        );
    }
}
