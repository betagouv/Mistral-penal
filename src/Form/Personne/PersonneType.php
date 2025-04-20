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
namespace App\Form\Personne;

use App\Entity\Personne;
use App\Entity\Pays;
use App\Entity\Nationalite;
use App\Entity\SituationFamilliale;
use App\Entity\FormeJuridique;
use App\Entity\Commune;
use App\Entity\CategoriePenale;
use App\Entity\AntecedentJudiciaire;
use App\Entity\Civilite;
use App\Entity\SansDomicile;
use App\Form\AdresseType;
use App\Form\RichCheckboxType;
use App\Form\ParenteType;
use App\Repository\AntecedentJudiciaireRepository;
use App\Repository\CategoriePenaleRepository;
use App\Repository\CiviliteRepository;
use App\Repository\CommuneRepository;
use App\Repository\NationaliteRepository;
use App\Repository\FormeJuridiqueRepository;
use App\Repository\PaysRepository;
use App\Repository\SansDomicileRepository;
use App\Repository\SituationFamillialeRepository;
use Doctrine\ORM\QueryBuilder;
use PUGX\AutocompleterBundle\Form\Type\AutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('id', HiddenType::class, [
            'mapped' => false,
          ])
          ->add('nom', TextType::class, [
            'label' => 'personne.field.nom',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave fr-header-title',
            ],
            'required' => false,
          ])
          ->add('raisonSociale', TextType::class, [
            'label' => 'personne.field.raison_sociale',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave fr-header-title',
            ],
            'required' => false,
          ])
          ->add('sigle', TextType::class, [
            'label' => 'personne.field.sigle',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('enseigne', TextType::class, [
            'label' => 'personne.field.enseigne',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('sirenSiret', TextType::class, [
            'label' => 'personne.field.siren_siret',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('isPersonneMorale',  RichCheckboxType::class, [
            'label' => 'personne.field.is_personne_morale',
            'required' => false,
          ])
          ->add('nomUsage', TextType::class, [
            'label' => 'personne.field.nom_usage',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('prenom1', TextType::class, [
            'label' => false,
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave fr-header-title',
              'placeholder' => 'personne.field.prenom1',
            ],
            'required' => false,
          ])
          ->add('prenom2', TextType::class, [
            'label' => false,
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
              'placeholder' => 'personne.field.prenom2',
            ],
            'required' => false,
          ])
          ->add('prenom3', TextType::class, [
            'label' => false,
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
              'placeholder' => 'personne.field.prenom3',
            ],
            'required' => false,
          ])
          ->add('paysNaissance', EntityType::class, [
            'choice_label' => 'libelleCourt',
            'label' => 'personne.field.paysNaissance',
            'class' => Pays::class,
            'query_builder' => function (PaysRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
            },
            'attr' => [
              'class' => 'fr-select fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('adresse', AdresseType::class, [
            'label' => 'personne.field.adresse'
          ])
          ->add('dateNaissance', DateType::class, [
            'widget' => 'single_text',
            // 'format' => 'dd/MM/yyyy',
            'html5' => true,
            'label' => 'personne.field.dateNaissance',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
              'placeholder' => 'globals.date',
            ],
            'required' => false,
          ])
          ->add('communeNaissance', AutocompleteType::class, [
            'class' => Commune::class,
            'label' => 'affaire_natinf.field.commune',
            'attr' => ['class' => 'fr-input fr-fieldset-autosave autocomplete_commune fr-autocomplete'],
            'required' => false,
          ])
          ->add('nationalite', EntityType::class, [
            'choice_label' => 'libelleCourt',
            'label' => 'personne.field.nationalite',
            'class' => Nationalite::class,
            'query_builder' => function (NationaliteRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
             },
             'attr' => [
               'class' => 'fr-input fr-fieldset-autosave',
             ],
             'required' => false,
          ])
          ->add('situationFamilliale', EntityType::class, [
            'choice_label' => 'libelleCourt',
            'label' => 'personne.field.situation_familliale',
            'class' => SituationFamilliale::class,
            'query_builder' => function (SituationFamillialeRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
             },
             'attr' => [
               'class' => 'fr-select fr-fieldset-autosave',
             ],
             'required' => false,
          ])
          ->add('formeJuridique', EntityType::class, [
            'choice_label' => 'libelleCourt',
            'label' => 'personne.field.forme_juridique',
            'class' => FormeJuridique::class,
            'query_builder' => function (FormeJuridiqueRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
             },
             'attr' => [
               'class' => 'fr-select fr-fieldset-autosave',
             ],
             'required' => false,
          ])
          ->add('categoriePenale', EntityType::class, [
            'choice_label' => 'libelleAvecMnemo',
            'label' => 'personne.field.categorie_penale',
            'class' => CategoriePenale::class,
            'query_builder' => function (CategoriePenaleRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
             },
             'attr' => [
               'class' => 'fr-select fr-fieldset-autosave',
             ],
             'required' => false,
          ])
          ->add('antecedentJudiciaire', EntityType::class, [
            'choice_label' => 'libelle',
            'label' => 'personne.field.antecedent_judiciaire',
            'class' => AntecedentJudiciaire::class,
            'query_builder' => function (AntecedentJudiciaireRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.id', 'ASC');
             },
             'attr' => [
               'class' => 'fr-select fr-fieldset-autosave',
             ],
             'required' => false,
          ])
          ->add('pere', ParenteType::class, [
            'label' => 'personne.field.pere',
            'required' => false,
          ])
          ->add('mere', ParenteType::class, [
            'label' => 'personne.field.mere',
            'required' => false,
          ])
          ->add('xSeDisant',  RichCheckboxType::class, [
            'label' => 'personne.field.xsedisant',
            'required' => false,
          ])
          ->add('isSansDomicile',  RichCheckboxType::class, [
            'label' => 'personne.field.is_sans_domicile',
            'required' => false,
          ])
          ->add('declarationAdresse',  CheckboxType::class, [
            'label' => 'personne.field.declarationAdresse',
            'attr' => [
              'class' => ''
            ],
            'required' => false,
          ])
          ->add('dateDeclarationAdresse',  DateType::class, [
            'label' => 'personne.field.dateDeclarationAdresse',
            // 'format' => 'dd/MM/yyyy',
            'widget' => 'single_text',
            'html5' => true,
            'label' => false,
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
              'placeholder' => 'personne.field.date_deces',
            ],
            'required' => false,
          ])
          ->add('isDecede',  RichCheckboxType::class, [
            'label' => 'personne.field.is_decede',
            'required' => false,
          ])
          ->add('dateDeces',  DateType::class, [
            'label' => 'personne.field.date_deces',
            // 'format' => 'dd/MM/yyyy',
            'widget' => 'single_text',
            'html5' => true,
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
              'placeholder' => 'personne.field.date_deces',
            ],
            'required' => false,
          ])
          ->add('profession', TextType::class, [
            'label' => 'personne.field.profession',
            'attr' => [
              'class' => 'fr-input fr-fieldset-autosave',
            ],
            'required' => false,
          ])
          ->add('civilite', EntityType::class, [
            'label' => 'personne.field.civilite',
            'choice_label' => 'libelle',
            'class' => Civilite::class,
            'attr' => [
              'class' => 'fr-select fr-fieldset-autosave',
            ],
            'query_builder' => function (CiviliteRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.id', 'ASC');
             },
          ])
          ->add('sansDomicile', EntityType::class, [
            'label' => 'personne.field.sansDomicile',
            'choice_label' => 'libelleCourt',
            'class' => SansDomicile::class,
            'query_builder' => function (SansDomicileRepository $er): QueryBuilder {
              return $er->createQueryBuilder('u')
                  ->orderBy('u.libelle', 'ASC');
             },
             'attr' => [
               'class' => 'fr-select fr-fieldset-autosave',
             ]
          ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personne::class,
        ]);
    }
}
