<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * monitor related strings
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */

$string['pluginname'] = 'Monitor';

// General strings.
$string['groupname'] = 'Grupo';
$string['discussionname'] = 'Discussão';
$string['tutorposts'] = 'Quantidade de posts feitos pelo Tutor';
$string['tutorpercent'] = 'Participação percentual do Tutor';
$string['tutorparticipation'] = 'Participação completa do Tutor';
$string['studentsposts'] = 'Quantidade de posts feitos pelos estudantes';
$string['responsetime'] = 'Tempo médio de resposta';

// Description strings.
$string['paramtimebetweenclicks'] = 'Tempo entre os cliques';
$string['paramstartdate'] = 'Data de início da consulta: Y-m-d ';
$string['paramend_date'] = 'Data final da consulta: Y-m-d ';
$string['paramtutorid'] = 'ID do Tutor';
$string['paramgroupid'] = 'ID do Grupo';
$string['parampesid'] = 'ID da Pessoa';
$string['paramtrmid'] = 'ID da Turma';

// Return strings.
$string['returnid'] = 'ID do Tutor no Moodle';
$string['returnfullname'] = 'Nome completo do tutor';
$string['returncoursefullname'] = 'Nome completo do curso';
$string['returnonlinetime'] = 'Tempo online em segundos';
$string['returndate'] = 'Data';

// Errors strings.
$string['timebetweenclickserror'] = 'Tempo entre os cliques deve ser maior que 0';
$string['startdateerror'] = 'Data de início deve ser anterior à data de fim';
$string['enddateerror'] = 'Data de fim deve ser igual ou anterior à data atual';
$string['databaseaccesserror'] = 'Erro ao acessar o banco de dados';
$string['tutornonexistserror'] = 'O pes_id não corresponde a nenhum Tutor conhecido';

// Endpoint descriptions.
$string['functiongettutoronlinetime'] = 'Tempo online do Tutor';
$string['functionping'] = 'Verifica a conexão com o Moodle';
$string['functiontutoransweres'] = 'Analíticas das respostas do Tutor aos fóruns';