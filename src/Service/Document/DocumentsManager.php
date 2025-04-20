<?php
/**
 * MIT License
 * 
 * Copyright (c) 2025 Mistral pénal - Incubateur du Minitère de la Justice
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace App\Service\Document;

use App\Entity\Audience;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

class DocumentsManager{

    private DocumentRole $documentRole;
    private DocumentNote $documentNote;

    public function __construct(
        DocumentRole $documentRole
        , DocumentNote $documentNote
    ){
        $this->documentRole = $documentRole;
        $this->documentNote = $documentNote;
    }

    public function createArchive(Audience $audience){

        $zip = new ZipArchive();
        $zipName = tempnam(sys_get_temp_dir(), 'monarchive');

        if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot open ' . $zipName);
        }

        $dirName = sys_get_temp_dir()."/Audience-".$audience->getId();
        if (!file_exists($dirName)) {
            mkdir($dirName);
        }

        // Creation Role
        $this->documentRole->create($audience, $dirName);

        //Creation des notes de chaque affaire(dossier)
        foreach($audience->getAffaires() as $affaire){
            $dirAffaire = $dirName."/Dossier".$affaire->getId()."-Audience".$audience->getDate()->format("dmY");
            if (!file_exists($dirAffaire)) {
                mkdir($dirAffaire);
            }
            $this->documentNote->create($affaire, $dirAffaire);
        }

        //Ajout des elements à l'archive
        if (is_dir($dirName)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirName),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $dir = str_replace($dirName.'/', '', $filePath);
                    $dir = str_replace($file->getFileName(), '', $dir);
                    // Add current file to archive
                    $zip->addFile($filePath, $dir.$file->getFilename());
                }else{
                    // create sub directory
                    if(str_contains($file->getRealPath(), $dirName.'/')) {
                        $newDir = str_replace($dirName.'/', '', $file->getRealPath());
                        $zip->addEmptyDir($newDir);
                    }
                }
            }
        } else {
            throw new NotFoundHttpException('Directory not found: ' . $dirName);
        }

        $zip->close();

        return $zipName;
    }
}