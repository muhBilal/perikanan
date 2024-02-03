<?php

namespace App\Http\Controllers\admin;

use App\DetailOrder;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Warehouse;
use App\Kedatangan;
use App\Fish;
use App\Size;
use App\Grade;
use App\KedatanganRack;
use App\PreOrder;
use App\Supplier;

use PDF;

class KedatanganController extends Controller
{
    public function index()
    {
        //ambil data order yang status nya 1 atau masih baru/belum melalukan pembayaran
        $kedatangan = Kedatangan::with('fish','grade','warehouse','size')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.kedatangan.index', compact('kedatangan'));
    }

    public function tambah()
    {
        //menampilkan form tambah kategori

        $warehouse = Warehouse::all();
        $fish = Fish::all();
        $size = Size::all();
        $grade = Grade::all();
        $supplier = Supplier::all();

        return view('admin.kedatangan.tambah', compact('warehouse', 'fish', 'size', 'grade', 'supplier'));
    }

    public function store(Request $request)
    {
        $grade = Grade::find($request->grade_id);
        $warehouse = Warehouse::find($request->warehouse_id);
        // $suplier = Warehouse::find($request->supplier_id);

        $kontainer = $request->kontainer;
        $urutan = $request->urutan;
        $date=date_create($request->date);
        $tanggal = date_format($date,"dmY");
        $code = $kontainer."/".$urutan."/".$warehouse->name."/SUP".$request->supplier_id."/".$tanggal;
        Kedatangan::updateOrCreate([
            'code' => $code,
            'date' => $request->date,
            'supplier_id' => $request->supplier_id,
            'warehouse_id' => $request->warehouse_id,
            'urutan' => $request->urutan,
            'fish_id' => $request->fish_id,
            'size_id' => $request->size_id,
            'grade_id' => $request->grade_id,
            'qty' => $request->qty,
            'kontainer' => $request->kontainer
        ], []);

        return redirect()->route('admin.kedatangan')->with('status', 'Berhasil Menambah Kedatangan');

    }

    public function edit(Kedatangan $id)
    {
        //menampilkan form edit
        //dan mengambil data produk sesuai id dari parameter

        $item = $id;

        $warehouse = Warehouse::all();
        $fish = Fish::all();
        $size = Size::all();
        $grade = Grade::all();
        $supplier = Supplier::all();

        return view('admin.kedatangan.edit', compact('warehouse', 'fish', 'size', 'grade', 'supplier', 'item'));
    }

    public function update(Request $request, Kedatangan $id)
    {
        $data = $request->all();

        $id->date = $data['date'];
        $id->kontainer = $data['kontainer'];
        $id->urutan = $data['urutan'];
        $id->warehouse_id = $data['warehouse_id'];
        $id->supplier_id = $data['supplier_id'];
        $id->fish_id = $data['fish_id'];
        $id->size_id = $data['size_id'];
        $id->grade_id = $data['grade_id'];
        $id->qty = $data['qty'];

        $id->save();

        return redirect()->route('admin.kedatangan')->with('status', 'Berhasil Mengubah Kedatangan');
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $item = Kedatangan::with('kedatanganRack')->findOrFail($id);
            $item->kedatanganRack()->delete();
            $item->delete();
            DB::commit();
            return redirect()->route('admin.kedatangan')->with('status', 'Berhasil Menghapus Produk');
        } catch (\Exception $e) {
            return redirect()->route('admin.kedatangan')->with('error', 'Gagal Menghapus Produk');
        }
    }


    public function cetak(Kedatangan $id)
    {
        $url = url()->previous();
        if($url === "http://localhost:8000/admin/kedatangan" || $url === "http://localhost:8000/admin/assign"){
            $pdf = PDF::loadview('admin.kedatangan.cetak',['data'=>$id]);
            return $pdf->stream('laporan-kedatangan-pdf.pdf');
        }else{
            $getOrder = DetailOrder::where('fish_id', $id->fish_id)->where('fish_size_id', $id->size_id)->where('fish_grade_id', $id->grade_id)->where('status', '!=', 'sukses')->first();
            if($getOrder){
                return $this->checkOrder($getOrder->id, $id);
            }else{
                return redirect()->route('admin.kedatangan');
            }
        }
    }

    public function checkOrder($id, Kedatangan $kedatangan) {
        $item = DetailOrder::find($id);
        $getAllKedatangan = Kedatangan::where('fish_id', $item->fish_id)->where('size_id', $item->fish_size_id)->where('grade_id', $item->fish_grade_id)->get();
        $po = PreOrder::find($item->order_id);

        if($item->status == 'sukses'){
            return response()->json(['message' => 'duplicate'], 200);
        }

        if(count($getAllKedatangan) > 0){
            $totalQty = $getAllKedatangan->sum('qty');
            $qtyCurrent = $item->qty;

            if($kedatangan->qty < $qtyCurrent){
                if($totalQty > $item->qty){
                    foreach($getAllKedatangan as $kedatangan_detail){
                        if($qtyCurrent != 0){
                            if ($qtyCurrent >= $kedatangan_detail->qty) {
                                $qtyCurrent -= $kedatangan_detail->qty;
                                $kedatangan_detail->qty = 0;
                            } else {
                                $kedatangan_detail->qty -= $qtyCurrent;
                                $qtyCurrent = 0;
                            }
                            $kedatangan_detail->save();
                        }else{
                            break;
                        }
                    }
                }else {
                    return response()->json(['message' => 'limit'], 200);
                }
            }else{
                $kedatangan->qty -= $qtyCurrent;
                $qtyCurrent = 0;
                $kedatangan->save();
            }

            $item->status = 'sukses';
            $item->save();

            $allPoItems = DetailOrder::where('order_id', $po->id)->get();

            $success = 0;
            foreach($allPoItems as $poItem){
                if($poItem->status == 'sukses'){
                    $success += 1;
                }
            }

            if($success == count($allPoItems)) {
                $po->status = 'sukses';
                $po->save();
            }

            return response()->json(['message' => 'success'], 200);
        }else{
            return response()->json(['message' => 'failed'], 500);
        }
    }

    public function detail($id)
    {
        //ambil data detail order sesuai id
        $detail_order = DetailOrder::join('products', 'products.id', '=', 'detail_order.product_id')
            ->join('order', 'order.id', '=', 'detail_order.order_id')
            ->select('products.name as nama_produk', 'products.image', 'detail_order.*', 'products.price', 'order.*')
            ->where('detail_order.order_id', $id)
            ->get();

        $order = Order::join('users', 'users.id', '=', 'order.user_id')
            ->join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->select('order.*', 'users.name as nama_pelanggan', 'status_order.name as status')
            ->where('order.id', $id)
            ->first();

        return view('admin.transaksi.detail', [
            'detail' => $detail_order,
            'order'  => $order
        ]);
    }

    public function perludicek()
    {
        //ambil data order yang status nya 2 atau belum di cek / sudah bayar
        $orderbaru = Order::join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->select('order.*', 'status_order.name', 'users.name as nama_pemesan')
            ->where('order.status_order_id', 2)
            ->get();

        return view('admin.transaksi.perludicek', compact('orderbaru'));
    }

    public function perludikirim()
    {
        //ambil data order yang status nya 3 sudah dicek dan perlu dikirim(input no resi)
        $orderbaru = Order::join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->select('order.*', 'status_order.name', 'users.name as nama_pemesan')
            ->where('order.status_order_id', 3)
            ->get();

        return view('admin.transaksi.perludikirim', compact('orderbaru'));
    }

    public function selesai()
    {
        //ambil data order yang status nya 5 barang sudah diterima pelangan
        $orderbaru = Order::join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->select('order.*', 'status_order.name', 'users.name as nama_pemesan')
            ->where('order.status_order_id', 5)
            ->get();

        return view('admin.transaksi.selesai', compact('orderbaru'));
    }

    public function dibatalkan()
    {
        //ambil data order yang status nya 6 dibatalkan pelanngan
        $orderbaru = Order::join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->select('order.*', 'status_order.name', 'users.name as nama_pemesan')
            ->where('order.status_order_id', 6)
            ->get();

        return view('admin.transaksi.dibatalkan', compact('orderbaru'));
    }

    public function dikirim()
    {
        //ambil data order yang status nya 4 atau sedang dikirim
        $orderbaru = Order::join('status_order', 'status_order.id', '=', 'order.status_order_id')
            ->join('users', 'users.id', '=', 'order.user_id')
            ->select('order.*', 'status_order.name', 'users.name as nama_pemesan')
            ->where('order.status_order_id', 4)
            ->get();

        return view('admin.transaksi.dikirim', compact('orderbaru'));
    }

    public function konfirmasi(Order $id)
    {
        //function ini untuk mengkonfirmasi bahwa pelanngan sudah melakukan pembayaran
        $id->update([
            'status_order_id' => 3
        ]);

        $order = DetailOrder::where('order_id', $id)->get();

        foreach ($order as $item) {
            Product::where('id', $item->product_id)->decrement('stok', $item->qty);
        }
        return redirect()->route('admin.transaksi.perludikirim')->with('status', 'Berhasil Mengonfirmasi Pembayaran Pesanan');
    }

    public function inputresi($id, Request $request)
    {
        //funtion untuk menginput no resi pesanan
        Order::where('id', $id)
            ->update([
                'no_resi'           => $request->no_resi,
                'status_order_id'   => 4
            ]);

        return redirect()->route('admin.transaksi.perludikirim')->with('status', 'Berhasil Menginput No Resi');
    }
}
