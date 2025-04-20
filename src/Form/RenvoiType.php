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
namespace App\Form;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Entity\Affaire;
use App\Entity\MesureSurete;
use App\Entity\Renvoi;
use App\Entity\RenvoiMotif;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as TypeDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RenvoiType extends AbstractType
{
    public function __construct(private IriConverterInterface $iriConverter)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $affaire = $options['affaire'];
        $builder
            ->add('id', HiddenType::class, [
              'mapped' => false
            ])
            ->add('affaire', EntityType::class, [
              'block_prefix' => '_affaire_hidden',
              'choice_label' => 'id',
              'choices' => [$affaire],
              'label' => false,
              'class' => Affaire::class,
              'data' => $options['affaire'],
              'attr' => [
                'class' => 'fr-select',
              ]
            ])
            ->add('renvoiMotif', EntityType::class, [
              'choice_label' => 'libelle',
              'label' => 'renvoi.field.motif',
              'class' => RenvoiMotif::class,
              'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.id', 'ASC');
              },
              'attr' => [
                'class' => 'fr-select',
              ]
            ])
            ->add('date',  TypeDateTimeType::class, [
              'widget' => 'single_text',
              'html5' => true,
              'label' => "renvoi.field.date",
              'view_timezone' => 'Europe/Paris',
              'attr' => [
                'class' => 'fr-input',
                'placeholder' => 'renvoi.field.date.placeholder',
              ],
              'required' => true,
            ])
            ->add('mesureSurete', EntityType::class, [
                'choice_label' => 'libelle',
                'choice_value' => function (?MesureSurete $entity) {
                    return $entity ? $this->iriConverter->getIriFromResource($entity) : '';
                },
                'label' => 'renvoi.field.mesureSurete.title',
                'class' => MesureSurete::class,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('u')->orderBy('u.libelle', 'ASC');
                },
                'placeholder' => 'renvoi.field.mesureSurete.empty',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'required' => false,
            ])
            ->add('detailsMesureSurete', TextareaType::class, [
                'label' => 'renvoi.field.detailsMesureSurete.title',
                'attr' => [
                    'class' => 'fr-input',
                    'placeholder' => 'renvoi.field.detailsMesureSurete.placeholder',
                    'rows' => 3,
                ],
                'required' => false,
            ])
            ->add('expertise', TextareaType::class, [
                'label' => 'renvoi.field.expertise.title',
                'attr' => [
                    'class' => 'fr-input',
                    'placeholder' => 'renvoi.field.expertise.placeholder',
                    'rows' => 3,
                ],
                'required' => false,
            ])
/*            ->add('affairePersonnes', EntityType::class, [
              'label' => 'renvoi.field.affairePersonnes',
              'block_prefix' => '_dsfr_checkbox',
              'class'    => AffairePersonne::class,
              'multiple' => true,
              'query_builder' => function (EntityRepository $er)use ($affaire) : QueryBuilder   {
                return $er->createQueryBuilder('u')
                    ->join('u.statut', 's')
                    ->andWhere('u.affaire = :affaire')
                    ->andWhere("s.code IN (:codes)")
                    ->setParameter('affaire', $affaire)
                    ->setParameter('codes', [
                      StatutPersonne::CODE_PREVENU,
                      StatutPersonne::CODE_MIS_EN_CAUSE,
                      StatutPersonne::CODE_VICTIME,
                      StatutPersonne::CODE_PARTIE_CIVILE,
                      StatutPersonne::CODE_PARTIE_CIVILE_RL,
                      StatutPersonne::CODE_PARTIE_CIVILE_ABUSIVE,
                      StatutPersonne::CODE_PARTIE_CIVILE_OPPOSANT,
                    ])
                    ->orderBy('u.id', 'ASC');
              },
              'label_html' => true,
              'choice_label'  => function (AffairePersonne $affairePersonne) {
                  // @var ?StatutPersonne $statutPersonne
                  $statutPersonne = $affairePersonne->getStatut();
                  // @var Personne $personne
                  $personne = $affairePersonne->getPersonne();
                  return $personne->getSmallNomComplet()
                         .'<span class="fr-pl-4w renvoi-modal-statut">'.($statutPersonne?$statutPersonne->getLibelle():'').'</span>';
              },
              'expanded' => true,
            ])
            */
            ->add('submit', SubmitType::class, [
              'label' => 'Enregistrer',
              'attr' => [
                'class' => 'fr-btn'
              ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Renvoi::class,
        ]);
        $resolver->setRequired('affaire');
    }
}
