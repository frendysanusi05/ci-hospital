<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Kunjungan;
use App\Models\Pasien;
use App\Models\Dokter;
use DateTime;

class KunjunganController extends BaseController
{
    protected $kunjungan;
    protected $pasiens;
    protected $dokters;

    function __construct() {
        $this->kunjungan = new Kunjungan();
        $this->pasiens = new Pasien();
        $this->dokters = new Dokter();
    }

    public function index()
    {
        $res = $this->getKunjungan(false);
        $visits = [];

        foreach ($res as $visit) {
            $id_pasien = $visit['id_pasien'];

            $pasien = $this->pasiens->find($id_pasien);
            $visit['nama_pasien'] = $pasien['nama'];

            $visits[] = $visit;
        }

        return view('d_visits', compact('visits'));
    }

    public function showNewVisits()
    {
        $pasien = $this->pasiens->find();
        $dokter = $this->dokters->find();
        
        $client = \Config\Services::curlrequest();
        $url = 'http://localhost:8080/api/obat';
        $res = $client->request('GET', $url);
        $body = $res->getBody();
        $body = json_decode($body, true);
    
        $obat = $body['data'];

        return view('d_visit_a', compact('pasien', 'dokter', 'obat'));
    }

    public function getKunjungan()
    {
        try {
            $data = $this->kunjungan->findAll();
            return $data;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    public function getKunjunganById($id)
    {
        $data = $this->kunjungan->find($id);
        if (empty($data)) {
            return null;
        }
        else {
            return $data;
        }
    }

    public function updateKunjungan($id) {
        $body = (array) $this->request->getJSON();

        try {
            $data = $this->kunjungan->update($id, [
                'tanggal'       => new DateTime($body['tanggal'] . ' ' . $body['waktu']),
                'id_pasien'     => $body['id_pasien'],
                'id_dokter'     => $body['id_dokter'],
                'keluhan'       => $body['keluhan'],
                'diagnosa'      => $body['diagnosa'],
                'preskripsi'    => $body['preskripsi'],
            ]);
    
            return redirect()->to('/doctor/visits');
        }
        catch (\Exception $e) {
            return redirect()->to('/doctor/visitsForm');
        }
    }

    public function getPreskripsi($id)
    {
        try {
            $data = $this->getKunjunganById($id);
            $preskripsi = json_decode($data['preskripsi'], true);
            $array_id_obat = array_column($preskripsi, 'id_obat');
            $array_id_obat = array_map('intval', $array_id_obat);

            $client = \Config\Services::curlrequest();
            
            $array_obat = [];
            foreach ($array_id_obat as $id_obat) {
                $url = 'http://localhost:8080/api/obat/' . $id_obat;
                $res = $client->request('GET', $url);
                $body = $res->getBody();
                $body = json_decode($body, true);
                $array_obat[$id_obat] = $body['data']['nama'];
            }
            
            return $array_obat;
        }
        catch (\Exception $e) {
            return null;
        }
    }
}
