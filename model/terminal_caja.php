<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of terminal_caja
 *
 * @author carlos
 */
class terminal_caja extends fs_model
{
   public $id;
   public $codalmacen;
   public $codserie;
   public $codcliente;
   public $tickets;
   public $anchopapel;
   public $comandocorte;
   public $comandoapertura;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('cajas_terminales', 'plugins/facturacion_base/');
      if($t)
      {
         $this->id = $this->intval($t['id']);
         $this->codalmacen = $t['codalmacen'];
         $this->codserie = $t['codserie'];
         $this->codcliente = $t['codcliente'];
         $this->tickets = $t['tickets'];
         
         $this->anchopapel = 40;
         if( isset($t['anchopapel']) )
         {
            $this->anchopapel = intval($t['anchopapel']);
         }
         
         $this->comandocorte = '27.105';
         if( isset($t['comandocorte']) )
         {
            $this->comandocorte = $t['comandocorte'];
         }
         
         $this->comandoapertura = '27.112.48';
         if( isset($t['comandoapertura']) )
         {
            $this->comandoapertura = $t['comandoapertura'];
         }
      }
      else
      {
         $this->id = NULL;
         $this->codalmacen = NULL;
         $this->codserie = NULL;
         $this->codcliente = NULL;
         $this->tickets = '';
         $this->anchopapel = 40;
         $this->comandocorte = '27.105';
         $this->comandoapertura = '27.112.48';
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function disponible()
   {
      if( $this->db->select("SELECT * FROM cajas WHERE f_fin IS NULL AND fs_id = ".$this->var2str($this->id).";") )
      {
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function add_linea($linea)
   {
      $this->tickets .= $linea;
   }
   
   public function add_linea_big($linea)
   {
      $this->tickets .= chr(27).chr(33).chr(56).$linea.chr(27).chr(33).chr(1);
   }
   
   public function abrir_cajon()
   {
      $aux = explode('.', $this->comandoapertura);
      if($aux)
      {
         foreach($aux as $a)
            $this->tickets .= chr($a);
      }
      
      $this->tickets .= ' ';
   }
   
   public function cortar_papel()
   {
      $aux = explode('.', $this->comandocorte);
      if($aux)
      {
         foreach($aux as $a)
            $this->tickets .= chr($a);
      }
      
      $this->tickets .= ' ';
   }
   
   public function center_text($word = '')
   {
      if( strlen($word) == $this->anchopapel )
      {
         return $word;
      }
      else if( strlen($word) < $this->anchopapel )
      {
         return $this->center_text2($word, $this->anchopapel);
      }
      else
      {
         $result = '';
         $nword = '';
         foreach( explode(' ', $word) as $aux )
         {
            if($nword == '')
            {
               $nword = $aux;
            }
            else if( strlen($nword) + strlen($aux) + 1 <= $this->anchopapel )
            {
               $nword = $nword.' '.$aux;
            }
            else
            {
               if($result != '')
                  $result .= "\n";
               $result .= $this->center_text2($nword);
               $nword = $aux;
            }
         }
         if($nword != '')
         {
            if($result != '')
               $result .= "\n";
            $result .= $this->center_text2($nword);
         }
         return $result;
      }
   }
   
   private function center_text2($word = '')
   {
      $symbol = " ";
      $middle = round($this->anchopapel / 2);
      $length_word = strlen($word);
      $middle_word = round($length_word / 2);
      $last_position = $middle + $middle_word;
      $number_of_spaces = $middle - $middle_word;
      $result = sprintf("%'{$symbol}{$last_position}s", $word);
      for($i = 0; $i < $number_of_spaces; $i++)
      {
         $result .= "$symbol";
      }
      return $result;
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM cajas_terminales WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new terminal_caja($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM cajas_terminales WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE cajas_terminales SET codalmacen = ".$this->var2str($this->codalmacen).
                 ", codserie = ".$this->var2str($this->codserie).
                 ", codcliente = ".$this->var2str($this->codcliente).
                 ", tickets = ".$this->var2str($this->tickets).
                 ", anchopapel = ".$this->var2str($this->anchopapel).
                 ", comandocorte = ".$this->var2str($this->comandocorte).
                 ", comandoapertura = ".$this->var2str($this->comandoapertura).
                 " WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO cajas_terminales (codalmacen,codserie,codcliente,tickets,anchopapel,"
                 . "comandocorte,comandoapertura) VALUES (".
                 $this->var2str($this->codalmacen).",".
                 $this->var2str($this->codserie).",".
                 $this->var2str($this->codcliente).",".
                 $this->var2str($this->tickets).",".
                 $this->var2str($this->anchopapel).",".
                 $this->var2str($this->comandocorte).",".
                 $this->var2str($this->comandoapertura).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM cajas_terminales WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all()
   {
      $tlist = array();
      
      $data = $this->db->select("SELECT * FROM cajas_terminales ORDER BY id ASC;");
      if($data)
      {
         foreach($data as $d)
            $tlist[] = new terminal_caja($d);
      }
      
      return $tlist;
   }
   
   public function disponibles()
   {
      $tlist = array();
      
      $data = $this->db->select("SELECT * FROM cajas_terminales WHERE id NOT IN (SELECT fs_id as id FROM cajas WHERE f_fin IS NULL) ORDER BY id ASC;");
      if($data)
      {
         foreach($data as $d)
            $tlist[] = new terminal_caja($d);
      }
      
      return $tlist;
   }
}