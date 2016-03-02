<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('cliente.php');
require_model('proveedor.php');
require_model('orden_prov');
/**
 * Esta clase permite genera un asiento a partir de una factura.
 *
 * @author Carlos García Gómez  neorazorx@gmail.com
 */
class asiento_factura
{
   public $asiento;
   private $ejercicio;
   
   public $messages;
   public $errors;
   public $soloasiento;
   
   public function __construct()
   {
      $this->asiento = FALSE;
      $this->ejercicio = new ejercicio();
      
      $this->messages = array();
      $this->errors = array();
      $this->soloasiento = FALSE;
   }
   
   private function new_message($msg)
   {
      $this->messages[] = $msg;
   }
   
   private function new_error_msg($msg)
   {
      $this->errors[] = $msg;
   }
  /////////////////////////////////////////
  ////////////////////////////////////////
  
  
   /////////////////////////////////////////////////////////
 /////////  DEVOLUCIÓN
 //////////////////////////////////////////////////////
 
   public function nuevo_asiento_devolucion_prov($id)
   {
   
   
	  $varfactura = new factura_proveedor();
	  $factura=$varfactura->get($_REQUEST['id']);	// con id toma de factura codproveedor,codejercicio, url(),codigo factura, nombreproveeedor fecha total

      $ok = FALSE;
      $this->asiento = FALSE;
      $proveedor0 = new proveedor();
      $subcuenta_prov = FALSE; // toma de proveedor subcuenta con el codejercicio
      
      $proveedor = $proveedor0->get($factura->codproveedor);
      if($proveedor)
      {
         $subcuenta_prov = $proveedor->get_subcuenta($factura->codejercicio);
      }
      
      if( !$subcuenta_prov )
      {
         $eje0 = $this->ejercicio->get( $factura->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el proveedor ");
         
         if(!$this->soloasiento)
         {
            $this->new_message("Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
         }
      }
      else
      {
	     $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Nota de crédito ".$factura->codigo." - ".$factura->nombre;
         $asiento->documento = $factura->codigo;
         $asiento->editable = TRUE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = "Egreso proveedor";
	 
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
			
			$lineas_f = new linea_factura_proveedor();
			$lineas_fact = $lineas_f->all_from_factura($factura->idfactura);
			
			
			
	//		 $subcuenta_compras = $subcuenta->get_cuentaesp('DEVCOM', $asiento->codejercicio);


               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
			   $partida2->idsubcuenta = $subcuenta_prov->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_prov->codsubcuenta;
///////// Proveedor compra debe  ////////////////////////			   
               $partida2->debe = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               $partida2->codserie = $factura->codserie;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
        
			
			
			
				//////////////////////////////////////////////////////////
	///  Separa las subcuentas de las facturas y crea los asientos
	//////////////////////////////////////////////////////////
			
			for ($i=0;$i<count($lineas_fact);$i++)   ///Barre la Factura
			{
			$total_sub = 0;
			$finaliza = 0;
				$subcuenta1 = $lineas_fact[$i]->codsubcuenta; 
				$idsubcuen = $lineas_fact[$i]->idsubcuenta; 
				for ($j=0;$j<$i;$j++)    /// Compara para atrás si ya fue comparada la subcuenta
				{
					if($subcuenta1 == $lineas_fact[$j]->codsubcuenta) $finaliza = 1;
				}				
					if( $finaliza == 0 )
					{
						for ($k=0;$k<count($lineas_fact);$k++)  /// Barre hacia delante para comparar las subcuentas
						{
							if($subcuenta1 == $lineas_fact[$k]->codsubcuenta)
							{
							$total_sub = $total_sub + $lineas_fact[$k]->pvptotal;								
							}				
						}
						
					/////// Acá se genera la partida con cada subcuenta	
						$subcuenta_compras = $subcuenta->get_cuentaesp('DEVCOM', $asiento->codejercicio);
		
									
							$partida0->idasiento = $asiento->idasiento;
							$partida0->concepto = $asiento->concepto;
							$partida0->idsubcuenta = $idsubcuen;
							$partida0->codsubcuenta = $subcuenta1;
				///////////  Proveedor  haber			/////////
							$partida0->haber = $total_sub;
							$partida0->coddivisa = $factura->coddivisa;
							$partida0->tasaconv = $factura->tasaconv;
							$partida0->codserie = $factura->codserie;
							if( !$partida0->save() )
							{
							   $asiento_correcto = FALSE;
							   $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
							}
			
					}																
			}
/////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////	
 
	
		   
    
             
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
      }
      
      return $ok;
   }

	   /**
    * Genera el asiento contable para una devolucion factura de venta.
    * Devuelve TRUE si el asiento se ha generado correctamente, False en caso contrario.
    * Si genera el asiento, este es accesible desde $this->asiento.
    * @param type $factura
    */
 	  	
    public function nuevo_asiento_devolucion_cli($id)
   {
		$varfactura = new factura_cliente();
	  	$factura=$varfactura->get($_REQUEST['idfaccli']);	

      $ok = FALSE;
      $this->asiento = FALSE;
      $cliente0 = new cliente();
      $subcuenta_cli = FALSE;
      
      $cliente = $cliente0->get($factura->codcliente);
      if($cliente)
      {
         $subcuenta_cli = $cliente->get_subcuenta($factura->codejercicio);
      }
      
      if( !$subcuenta_cli )
      {
         $eje0 = $this->ejercicio->get( $factura->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el cliente
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>");
         
         if(!$this->soloasiento)
         {
            $this->new_message("Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
         }
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Nota de crédito ".$factura->codigo." - ".$factura->nombrecliente;
         $asiento->documento = $factura->codigo;
         $asiento->editable = TRUE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = 'Egreso cliente';
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
        	$subcuenta_ventas = $subcuenta->get_cuentaesp('DEVVEN', $asiento->codejercicio);			
			
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_ventas->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_ventas->codsubcuenta;
            $partida0->debe = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
            $partida0->codserie = $factura->codserie;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            
    
            if($subcuenta_ventas AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
			   $partida2->idsubcuenta = $subcuenta_cli->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_cli->codsubcuenta;
               $partida2->haber = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               $partida2->codserie = $factura->codserie;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            

            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
         }
      }
      
      return $ok;
   }			
				
   
 
 
 /////////////////////////////////////
 /////////////////////////////////////
   

 public function asiento_pago_compra($id)
   {

   
	  $varorden = new orden_prov();
	  $orden =  $varorden->get($id);

      $ok = FALSE;
      $this->asiento = FALSE;
      $proveedor0 = new proveedor();
      $subcuenta_prov = FALSE;

      $proveedor = $proveedor0->get($orden->codprovorden);
      if($proveedor)
      {
         $subcuenta_prov = $proveedor->get_subcuenta($orden->codejercicio);
      }
      if( !$subcuenta_prov )
      {
         $eje0 = $this->ejercicio->get($orden->codejercicio);
         $this->new_message("No se ha podido generar una subcuenta para el proveedor ");
         if(!$this->soloasiento)
         {
           $this->new_message("Aun así la <a href='".$orden->url()."'>factura</a> se ha generado correctamente,
          pero sin asiento contable.");
         }
      }
      else
      {
	     $asiento = new asiento();
         $asiento->codejercicio = $orden->codejercicio;
//         $asiento->concepto = "Orden de pago ".$orden->codigo." - ".$orden->nombre;
//         $asiento->documento = $orden->codigo;
		 $asiento->concepto = "Orden de pago ".$orden->fecha." - ".$orden->provorden;
         $asiento->documento = $orden->fecha;
		 $asiento->concepto = "Orden de pago  - ".$orden->provorden;
         $asiento->editable = TRUE;
         $asiento->fecha = $orden->fecha;
         $asiento->importe = $orden->importepagar;
         $asiento->tipodocumento = "Egreso proveedor";
	 
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_prov->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_prov->codsubcuenta;
///////////  Proveedor  debe			/////////
            $partida0->debe = $orden->importepagar;
            $partida0->coddivisa = 0;
            $partida0->tasaconv = 0;
            $partida0->codserie = 0;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }

		   
		     $subcuenta_compras = $subcuenta->get_cuentaesp('COMPRA',$asiento->codejercicio);
 
            if($subcuenta_compras AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_compras->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_compras->codsubcuenta;
///////// Proveedor compra haber  ////////////////////////			   
               $partida2->haber = $orden->importepagar;
               $partida2->coddivisa = 0;
               $partida2->tasaconv = 0;
               $partida2->codserie = 0;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if($asiento_correcto)
            {
               $orden->idasiento = $asiento->idasiento;
               if( $orden->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
      }
      
      return $ok;
   }			
				 
  
  
  
  //////////////////////////////////////////
  ////////////////////////////////////////// 
   
   
   /**
    * Genera el asiento contable para una factura de compra.
    * Devuelve TRUE si el asiento se ha generado correctamente, False en caso contrario.
    * Si genera el asiento, este es accesible desde $this->asiento.
    * @param type $factura
    */
   public function generar_asiento_compra(&$factura)
   {
      $ok = FALSE;
      $this->asiento = FALSE;
      $proveedor0 = new proveedor();
      $subcuenta_prov = FALSE;
      
      $proveedor = $proveedor0->get($factura->codproveedor);
      if($proveedor)
      {
	  /// Obtengo subcuenta proveedor
         $subcuenta_prov = $proveedor->get_subcuenta($factura->codejercicio);
      }
      
      if( !$subcuenta_prov )
      {
         $eje0 = $this->ejercicio->get( $factura->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el proveedor
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>");
         
         if(!$this->soloasiento)
         {
            $this->new_message("Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
         }
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Factura de compra ".$factura->codigo." - ".$factura->nombre;
         $asiento->documento = $factura->codigo;
         $asiento->editable = TRUE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = "Ingreso proveedor";

		 
         if( $asiento->save() ) // Genera el asiento
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
			$lineas_f = new linea_factura_proveedor();
			$lineas_fact = $lineas_f->all_from_factura($factura->idfactura);
	//////////////////////////////////////////////////////////
	///  Separa las subcuentas de las facturas y crea los asientos
	//////////////////////////////////////////////////////////
			
			for ($i=0;$i<count($lineas_fact);$i++)   ///Barre la Factura
			{
			$total_sub = 0;
			$finaliza = 0;
				$subcuenta1 = $lineas_fact[$i]->codsubcuenta; 
				$idsubcuen = $lineas_fact[$i]->idsubcuenta; 
				for ($j=0;$j<$i;$j++)    /// Compara para atrás si ya fue comparada la subcuenta
				{
					if($subcuenta1 == $lineas_fact[$j]->codsubcuenta) $finaliza = 1;
				}				
					if( $finaliza == 0 )
					{
						for ($k=0;$k<count($lineas_fact);$k++)  /// Barre hacia delante para comparar las subcuentas
						{
							if($subcuenta1 == $lineas_fact[$k]->codsubcuenta)
							{
							$total_sub = $total_sub + $lineas_fact[$k]->pvptotal;								
							}				
						}
						
					/////// Acá se genera la partida con cada subcuenta	
						$subcuenta_compras = $subcuenta->get_cuentaesp('COMPRA', $asiento->codejercicio);
		
									
							$partida0->idasiento = $asiento->idasiento;
							$partida0->concepto = $asiento->concepto;
							$partida0->idsubcuenta = $idsubcuen;
							$partida0->codsubcuenta = $subcuenta1;
				///////////  Proveedor  debe			/////////
							$partida0->debe = $total_sub;
							$partida0->coddivisa = $factura->coddivisa;
							$partida0->tasaconv = $factura->tasaconv;
							$partida0->codserie = $factura->codserie;
							if( !$partida0->save() )
							{
							   $asiento_correcto = FALSE;
							   $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
							}
			
					}																
			}
/////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////				

			
			
			
			
			
			
			
			
	

            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_cuentaesp('IVASOP', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->debe = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_prov->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_prov->codsubcuenta;
                  $partida1->cifnif = $proveedor->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
                  
                  if($li->recargo != 0)
                  {
                     $partida11 = new partida();
                     $partida11->idasiento = $asiento->idasiento;
                     $partida11->concepto = $asiento->concepto;
                     $partida11->idsubcuenta = $subcuenta_iva->idsubcuenta;
                     $partida11->codsubcuenta = $subcuenta_iva->codsubcuenta;
                     $partida11->debe = $li->totalrecargo;
                     $partida11->idcontrapartida = $subcuenta_prov->idsubcuenta;
                     $partida11->codcontrapartida = $subcuenta_prov->codsubcuenta;
                     $partida11->cifnif = $proveedor->cifnif;
                     $partida11->documento = $asiento->documento;
                     $partida11->tipodocumento = $asiento->tipodocumento;
                     $partida11->codserie = $factura->codserie;
                     $partida11->factura = $factura->numero;
                     $partida11->baseimponible = $li->neto;
                     $partida11->recargo = $li->recargo;
                     $partida11->coddivisa = $factura->coddivisa;
                     $partida11->tasaconv = $factura->tasaconv;
                     if( !$partida11->save() )
                     {
                        $asiento_correcto = FALSE;
                        $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida11->codsubcuenta."!");
                     }
                  }
               }
            }
            
  
            if($subcuenta_compras AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
			   $partida2->idsubcuenta = $subcuenta_prov->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_prov->codsubcuenta;
///////// Proveedor compra haber  ////////////////////////			   
               $partida2->haber = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               $partida2->codserie = $factura->codserie;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            /// ¿IRPF?
            if($factura->totalirpf != 0 AND $asiento_correcto)
            {
               $subcuenta_irpf = $subcuenta->get_cuentaesp('IRPFPR', $asiento->codejercicio);
               if($subcuenta_irpf)
               {
                  $partida3 = new partida();
                  $partida3->idasiento = $asiento->idasiento;
                  $partida3->concepto = $asiento->concepto;
                  $partida3->idsubcuenta = $subcuenta_irpf->idsubcuenta;
                  $partida3->codsubcuenta = $subcuenta_irpf->codsubcuenta;
                  $partida3->haber = $factura->totalirpf;
                  $partida3->coddivisa = $factura->coddivisa;
                  $partida3->tasaconv = $factura->tasaconv;
                  $partida3->codserie = $factura->codserie;
                  if( !$partida3->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida3->codsubcuenta."!");
                  }
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
			
			
			
			
         }
      }
      
      return $ok;
   }
   
     /**
    * Genera el asiento contable para una factura de CRÉDITO de compra.
    * Devuelve TRUE si el asiento se ha generado correctamente, False en caso contrario.
    * Si genera el asiento, este es accesible desde $this->asiento.
    * @param type $factura
    */
   public function generar_asiento_compra_credito(&$factura)
   {
      $ok = FALSE;
      $this->asiento = FALSE;
      $proveedor0 = new proveedor();
      $subcuenta_prov = FALSE;
      
      $proveedor = $proveedor0->get($factura->codproveedor);
      if($proveedor)
      {
	  /// Obtengo subcuenta proveedor
         $subcuenta_prov = $proveedor->get_subcuenta($factura->codejercicio);
      }
      
      if( !$subcuenta_prov )
      {
         $eje0 = $this->ejercicio->get( $factura->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el proveedor
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>");
         
         if(!$this->soloasiento)
         {
            $this->new_message("Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
         }
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Factura de compra ".$factura->codigo." - ".$factura->nombre;
         $asiento->documento = $factura->codigo;
         $asiento->editable = TRUE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = "Ingreso proveedor";

		 
         if( $asiento->save() ) // Genera el asiento
         {
			 $asiento_correcto = TRUE;
			 $subcuenta = new subcuenta();
		 	/////// Acá se genera la partida con cada subcuenta	
			$subcuenta_compras = $subcuenta->get_cuentaesp('COMPRA', $asiento->codejercicio);
            
////////////////////////////////////////////////////////////////////////////////////////
            if($subcuenta_compras AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
			   $partida2->idsubcuenta = $subcuenta_prov->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_prov->codsubcuenta;
///////// Proveedor compra debe  ////////////////////////			   
               $partida2->debe = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               $partida2->codserie = $factura->codserie;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }



/////////////////////////////////////////////////////////////////////////////////////////            

            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_cuentaesp('IVASOP', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->debe = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_prov->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_prov->codsubcuenta;
                  $partida1->cifnif = $proveedor->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
                  
                  if($li->recargo != 0)
                  {
                     $partida11 = new partida();
                     $partida11->idasiento = $asiento->idasiento;
                     $partida11->concepto = $asiento->concepto;
                     $partida11->idsubcuenta = $subcuenta_iva->idsubcuenta;
                     $partida11->codsubcuenta = $subcuenta_iva->codsubcuenta;
                     $partida11->debe = $li->totalrecargo;
                     $partida11->idcontrapartida = $subcuenta_prov->idsubcuenta;
                     $partida11->codcontrapartida = $subcuenta_prov->codsubcuenta;
                     $partida11->cifnif = $proveedor->cifnif;
                     $partida11->documento = $asiento->documento;
                     $partida11->tipodocumento = $asiento->tipodocumento;
                     $partida11->codserie = $factura->codserie;
                     $partida11->factura = $factura->numero;
                     $partida11->baseimponible = $li->neto;
                     $partida11->recargo = $li->recargo;
                     $partida11->coddivisa = $factura->coddivisa;
                     $partida11->tasaconv = $factura->tasaconv;
                     if( !$partida11->save() )
                     {
                        $asiento_correcto = FALSE;
                        $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida11->codsubcuenta."!");
                     }
                  }
               }
            }
            
/////////////////////////////////////////////////////////////////////////////////////  
			
	//////////////////////////////////////////////////////////
	///  Separa las subcuentas de las facturas y crea los asientos
	//////////////////////////////////////////////////////////	
			$partida0 = new partida();
			$lineas_f = new linea_factura_proveedor();
			$lineas_fact = $lineas_f->all_from_factura($factura->idfactura);
			for ($i=0;$i<count($lineas_fact);$i++)   ///Barre la Factura
			{
			$total_sub = 0;
			$finaliza = 0;
				$subcuenta1 = $lineas_fact[$i]->codsubcuenta; 
				$idsubcuen = $lineas_fact[$i]->idsubcuenta; 

				for ($j=0;$j<$i;$j++)    /// Compara para atrás si ya fue comparada la subcuenta
				{
					if($subcuenta1 == $lineas_fact[$j]->codsubcuenta) $finaliza = 1;
				}				
					if( $finaliza == 0 )
					{
						for ($k=0;$k<count($lineas_fact);$k++)  /// Barre hacia delante para comparar las subcuentas
						{
							if($subcuenta1 == $lineas_fact[$k]->codsubcuenta)
							{
							$total_sub = $total_sub + $lineas_fact[$k]->pvptotal;								
							}				
						}
						

		
									
							$partida0->idasiento = $asiento->idasiento;
							$partida0->concepto = $asiento->concepto;
							$partida0->idsubcuenta = $idsubcuen;
							$partida0->codsubcuenta = $subcuenta1;
				///////////  Proveedor  haber			/////////
							$partida0->haber = $total_sub;
							$partida0->coddivisa = $factura->coddivisa;
							$partida0->tasaconv = $factura->tasaconv;
							$partida0->codserie = $factura->codserie;
							if( !$partida0->save() )
							{
							   $asiento_correcto = FALSE;
							   $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
							}
			
					}																
			}
/////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////				


////////////////////////////////////////////////////////////////////////////////////////////            
            /// ¿IRPF?
            if($factura->totalirpf != 0 AND $asiento_correcto)
            {
               $subcuenta_irpf = $subcuenta->get_cuentaesp('IRPFPR', $asiento->codejercicio);
               if($subcuenta_irpf)
               {
                  $partida3 = new partida();
                  $partida3->idasiento = $asiento->idasiento;
                  $partida3->concepto = $asiento->concepto;
                  $partida3->idsubcuenta = $subcuenta_irpf->idsubcuenta;
                  $partida3->codsubcuenta = $subcuenta_irpf->codsubcuenta;
                  $partida3->haber = $factura->totalirpf;
                  $partida3->coddivisa = $factura->coddivisa;
                  $partida3->tasaconv = $factura->tasaconv;
                  $partida3->codserie = $factura->codserie;
                  if( !$partida3->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida3->codsubcuenta."!");
                  }
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
			
			
			
			
         }
      }
      
      return $ok;
   }
   
   /**
    * Genera el asiento contable para una factura de venta.
    * Devuelve TRUE si el asiento se ha generado correctamente, False en caso contrario.
    * Si genera el asiento, este es accesible desde $this->asiento.
    * @param type $factura
    */
   public function generar_asiento_venta(&$factura)
   {
      $ok = FALSE;
      $this->asiento = FALSE;
      $cliente0 = new cliente();
      $subcuenta_cli = FALSE;
      
      $cliente = $cliente0->get($factura->codcliente);
      if($cliente)
      {
         $subcuenta_cli = $cliente->get_subcuenta($factura->codejercicio);
      }
      
      if( !$subcuenta_cli )
      {
         $eje0 = $this->ejercicio->get( $factura->codejercicio );
         $this->new_message("No se ha podido generar una subcuenta para el cliente
            <a href='".$eje0->url()."'>¿Has importado los datos del ejercicio?</a>");
         
         if(!$this->soloasiento)
         {
            $this->new_message("Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente,
            pero sin asiento contable.");
         }
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Factura de venta ".$factura->codigo." - ".$factura->nombrecliente;
         $asiento->documento = $factura->codigo;
         $asiento->editable = TRUE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = 'Ingreso cliente';
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_cli->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_cli->codsubcuenta;
            $partida0->debe = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
            $partida0->codserie = $factura->codserie;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_cuentaesp('IVAREP', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->haber = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_cli->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_cli->codsubcuenta;
                  $partida1->cifnif = $cliente->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
                  
                  if($li->recargo != 0)
                  {
                     $partida11 = new partida();
                     $partida11->idasiento = $asiento->idasiento;
                     $partida11->concepto = $asiento->concepto;
                     $partida11->idsubcuenta = $subcuenta_iva->idsubcuenta;
                     $partida11->codsubcuenta = $subcuenta_iva->codsubcuenta;
                     $partida11->haber = $li->totalrecargo;
                     $partida11->idcontrapartida = $subcuenta_cli->idsubcuenta;
                     $partida11->codcontrapartida = $subcuenta_cli->codsubcuenta;
                     $partida11->cifnif = $cliente->cifnif;
                     $partida11->documento = $asiento->documento;
                     $partida11->tipodocumento = $asiento->tipodocumento;
                     $partida11->codserie = $factura->codserie;
                     $partida11->factura = $factura->numero;
                     $partida11->baseimponible = $li->neto;
                     $partida11->recargo = $li->recargo;
                     $partida11->coddivisa = $factura->coddivisa;
                     $partida11->tasaconv = $factura->tasaconv;
                     if( !$partida11->save() )
                     {
                        $asiento_correcto = FALSE;
                        $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida11->codsubcuenta."!");
                     }
                  }
               }
            }
            
            $subcuenta_ventas = $subcuenta->get_cuentaesp('VENTAS', $asiento->codejercicio);
            if($subcuenta_ventas AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_ventas->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_ventas->codsubcuenta;
               $partida2->haber = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               $partida2->codserie = $factura->codserie;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            /// ¿IRPF?
            if($factura->totalirpf != 0 AND $asiento_correcto)
            {
               $subcuenta_irpf = $subcuenta->get_cuentaesp('IRPF', $asiento->codejercicio);
               
               if(!$subcuenta_irpf)
               {
                  $subcuenta_irpf = $subcuenta->get_by_codigo('4730000000', $asiento->codejercicio);
               }
               
               if($subcuenta_irpf)
               {
                  $partida3 = new partida();
                  $partida3->idasiento = $asiento->idasiento;
                  $partida3->concepto = $asiento->concepto;
                  $partida3->idsubcuenta = $subcuenta_irpf->idsubcuenta;
                  $partida3->codsubcuenta = $subcuenta_irpf->codsubcuenta;
                  $partida3->debe = $factura->totalirpf;
                  $partida3->coddivisa = $factura->coddivisa;
                  $partida3->tasaconv = $factura->tasaconv;
                  $partida3->codserie = $factura->codserie;
                  if( !$partida3->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida3->codsubcuenta."!");
                  }
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
               {
                  $ok = TRUE;
                  $this->asiento = $asiento;
               }
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
         }
      }
      
      return $ok;
   }
}
