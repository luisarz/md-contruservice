<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Kardex;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ajustarController extends Controller
{

    public function index()
    {
        $productos = DB::table('update_inv')->get();
        $productosNoEncontrados = [];
        $productosEncontrados = [];
        foreach ($productos as $produ) {
            $producto = Product::where('id', $produ->id)
                ->first();


            if ($producto) {
                $productosEncontrados[] = $producto;
                $inventario = Inventory::where('product_id', $producto->id)->first();
                if ($inventario) {
                    $kardex = Kardex::where('inventory_id', $inventario->id)
                        ->where('operation_type', 'INVENTARIO INICIAL')
                        ->first();
                    if ($kardex) {
                        // Validaciones de producto
                        $stock = is_numeric($produ->stock) && $produ->stock >= 0 ? (float)$produ->stock : 0;
                        $costo = is_numeric($produ->costo) && $produ->costo >= 0 ? (float)$produ->costo : 0;

// Asignación al kardex con valores validados
                        $kardex->stock_in = $stock;
                        $kardex->stock_actual = $stock;
                        $kardex->money_in = $costo * $stock;
                        $kardex->money_actual = $costo * $stock;
                        $kardex->purchase_price = $costo;
                        $kardex->promedial_cost = $costo;

                        $kardex->save();

                    }
                }

            } else {
                $productosNoEncontrados[] = $produ;
            }

        }
        return response()->json([
            'productos_no_encontrados' => $productosNoEncontrados,
            'total_productos_no_encontrados' => count($productosNoEncontrados),
            'productos_encontrados' => $productosEncontrados,
            'total_productos_encontrados' => count($productosEncontrados),
            'total_productos' => count($productos),
        ]);


//        $kardex = Kardex::where('operation_type', 'INVENTARIO INICIAL')->get();
//        $inventario_sin_ajustar = [];
//        $inventario_ajustado = [];
//        foreach ($kardex as $kardex_destino) {
//
//            $id = $kardex_destino->inventory_id;
//            //seleccionar de la tabla inventory_ajust el ultimo registro con ese id de inventory
//            $kardex_origen = DB::table('inventario_ajustar')->where('item', $id)->first();
//            if ($kardex_origen) {
//                $tipo_ajuste = $kardex_origen->ESTADO;
//                if ($tipo_ajuste == 'SALIDA') {
//                    //disminuimos la cantidad ajustada
//                    $kardex_destino->stock_in = $kardex_destino->stock_in - $kardex_origen->CANTIDAD_AJUSTADA;
//                    $kardex_destino->stock_actual= $kardex_destino->stock_in - $kardex_origen->CANTIDAD_AJUSTADA;
//                    $kardex_destino->save();
//                    $inventario_ajustado[] = $kardex_origen;
//                } else {
//                    //aumentamos la cantidad ajustada
//                    $kardex_destino->stock_in = $kardex_destino->stock_in + $kardex_origen->CANTIDAD_AJUSTADA;
//                    $kardex_destino->stock_actual= $kardex_destino->stock_in + $kardex_origen->CANTIDAD_AJUSTADA;
//                    $kardex_destino->save();
//                    $inventario_ajustado[] = $kardex_destino;
//                }
//            } else {
//                $inventario_sin_ajustar[] = $kardex_destino;
//            }
//
//
//        }
//        $data=[
//            'inventario_ajustado'=>$inventario_ajustado,
//            'inventario_sin_ajustar'=>$inventario_sin_ajustar,
//        ];
//        return response()->json($data);


    }
    //
}
