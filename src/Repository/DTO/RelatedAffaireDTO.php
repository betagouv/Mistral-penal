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
namespace App\Repository\DTO;
use App\Entity\StatutPersonne;
use App\Service\CryptologyHelper;

class RelatedAffaireDTO {

    private int $id;
    private ?string $numeroParquet;
    private ?string $prenom1;
    private ?string $nom;
    private ?string $raisonSociale;
    private ?string $statut;

    public function __construct(int $id, ?string $numeroParquet, ?string $prenom1, ?string $nom, ?string $raisonSociale, ?string $statut) {
        $this->id = $id;
        $this->numeroParquet = CryptologyHelper::decrypt($numeroParquet);
        $this->prenom1 = CryptologyHelper::decrypt($prenom1);
        $this->nom = CryptologyHelper::decrypt($nom);
        $this->raisonSociale = CryptologyHelper::decrypt($raisonSociale);
        $this->statut = $statut;
    }

    public function getId() {
        return $this->id;
    }

    public function getNomComplet() {
        if (!empty($this->raisonSociale)) {
            return trim(mb_strtoupper($this->raisonSociale));
        }

        return trim(mb_strtoupper($this->nom)) . " " . trim($this->prenom1);
    }

    public function getNumeroDossier() {
      return substr($this->numeroParquet, -11);
    }

    public function isPrevenu() {
        return in_array($this->statut, [
            StatutPersonne::CODE_PREVENU,
            StatutPersonne::CODE_MIS_EN_CAUSE,
        ]);
    }

    public function isVictime() {
        return in_array($this->statut, [
            StatutPersonne::CODE_VICTIME,
            StatutPersonne::CODE_PARTIE_CIVILE,
            StatutPersonne::CODE_PARTIE_CIVILE_RL,
            StatutPersonne::CODE_PARTIE_CIVILE_ABUSIVE,
            StatutPersonne::CODE_PARTIE_CIVILE_OPPOSANT
        ]);

    }

    public function isJuge() {
        return in_array($this->statut, [
            StatutPersonne::CODE_JUGE
        ]);
    }
}