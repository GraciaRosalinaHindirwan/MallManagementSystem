<?php

class Feedback
{
    public int $id;
    public string $nama_pengunjung;
    public int $rating;
    public string $komentar;
    public string $kategori;
    public DateTime $created_at;
    public DateTime $updated_at;

    public function __construct(
        int $id,
        string $nama_pengunjung,
        int $rating,
        string $komentar,
        string $kategori,
        DateTime $created_at,
        DateTime $updated_at,
    ) {
        $this->id = $id;
        $this->nama_pengunjung = $nama_pengunjung;
        $this->rating = $rating;
        $this->komentar =  $komentar;
        $this->kategori = $kategori;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function create_with_rating(int $id, int $rating)
    {
        return new Feedback(
            $id,
            "John doe",
            $rating,
            "komentar",
            "kategori",
            new DateTime(),
            new DateTime()
        );
    }
}
