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

use App\Entity\Affaire;
use App\Entity\Intervenant;
use App\Form\Affaire\IntervenantsType;
use App\Form\AffairePersonne\PrevenuType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffaireType extends AbstractType
{
    public function appendIntervenant(
      FormBuilderInterface $builder,
      string $field,
      string $label,
    ): self {
      $builder
        ->add($field,TextType::class, [
          'attr'     => ['class'       => 'fr-input fr-fieldset-autosave'],
          'label'    => $label,
          'required' => false,
        ]);
      return $this;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Affaire $affaire */
        $affaire = $options['affaire'];
        /** @var Collection<int, AffairePersonne> $prevenus */
        $prevenus = $affaire->getPrevenus();
        /** @var Collection<int, AffairePersonne> $victimes */
        $victimes = $affaire->getVictimes();
        $this
          ->appendIntervenant($builder, 'president', 'affaire.intervenant.president')
          ->appendIntervenant($builder, 'ministerePublic', 'affaire.intervenant.ministere')
          ->appendIntervenant($builder, 'assesseur1', 'affaire.intervenant.assesseur1')
          ->appendIntervenant($builder, 'assesseur2', 'affaire.intervenant.assesseur2')
          ->appendIntervenant($builder, 'greffier', 'affaire.intervenant.greffier')
        ;

        $builder
            ->add('submit', SubmitType::class, [
              'label' => 'Enregistrer',
              'attr' => [
                'class' => 'fr-btn'
              ]
            ])
            ->add('numeroParquet', HiddenType::class, [
              'label' => 'affaire.field.numero_parquet',
            ])
            ->add('prevenus', CollectionType::class, [
              'entry_type'    => PrevenuType::class,
              'data'          => $prevenus,
              //'mapped'        => false,
            ])
            ->add('victimes', CollectionType::class, [
              'entry_type'    => PrevenuType::class,
              'data'          => $victimes,
              'allow_add'     => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Affaire::class,
        ]);
        $resolver->setRequired('affaire');
    }
}
