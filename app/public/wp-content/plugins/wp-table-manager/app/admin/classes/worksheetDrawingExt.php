<?php
class WorksheetDrawingExt {
    private $idx = array();

    /**
     * Upon construction will build an index drawings and their locations
     */
    public function __construct(PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws) {
        $this->createDrawingIndex($ws);
    }

    private function addDrawingToCell($coord,$drawing) {
        if (array_key_exists($coord,$this->idx)) {
            //There's already one image here - append a new
            $this->idx[$coord][]=$drawing;
        } else {
            //No images so far, setup the base array
            $this->idx[$coord]=[$drawing];
        }
    }

    private function createDrawingIndex($ws) {
        $drawings=$ws->getDrawingCollection();
        foreach ($drawings as $drawing) {
            $coord=$drawing->getCoordinates();//Inconsistent plural!
            $this->addDrawingToCell($coord,$drawing);
        }
    }

    /**
     * Get all drawings for a cell (always returns an array even if there's 1 or less images)
     */
    public function drawingsForCell(PhpOffice\PhpSpreadsheet\Cell\Cell $cell):array {
        return $this->drawingsForCoordinate($cell->getCoordinate());
    }

    /**
     * Get all drawings at the given coordinate (always returns an array even if there's 1 or less images)
     */
    public function drawingsForCoordinate(string $coordinate):array {
        if (array_key_exists($coordinate,$this->idx)) {
            return $this->idx[$coordinate];
        } else {
            return [];
        }
    }
}
