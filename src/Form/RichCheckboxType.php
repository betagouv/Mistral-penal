<?php
namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

final class RichCheckboxType extends AbstractType implements DataMapperInterface {

  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('localCheckbox', CheckboxType::class, [
        'required' => false,
        'attr' => [
          'class' => 'fr-toggle__input',
        ],
        'label' => $options['label']??null,
      ])
      ->setDataMapper($this)
    ;
  }

  public function convertStringToBoolean(mixed $viewData): bool
  {
    if("" === $viewData)
      return false;
    return $viewData;
  }
  public function mapDataToForms($viewData, iterable $forms): void
  {
    // there is no data yet, so nothing to prepopulate
    if (null === $viewData) { return; }

    // invalid data type
    //if (!$viewData instanceof \DateTime) {
    //    throw new UnexpectedTypeException($viewData, \DateTime::class);
    //}

    /** @var FormInterface[] $forms */
    $forms = iterator_to_array($forms);
    if(!empty($forms['localCheckbox'])) {
      $output = self::convertStringToBoolean($viewData);
      $forms['localCheckbox']->setData($output);
    }
  }

  public function mapFormsToData(iterable $forms, &$viewData): void {
    /** @var FormInterface[] $forms */
    $forms = iterator_to_array($forms);
    $viewData = $forms['localCheckbox']->getData();
  }

}
