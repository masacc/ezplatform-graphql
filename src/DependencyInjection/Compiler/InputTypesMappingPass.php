<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformGraphQL\DependencyInjection\Compiler;

use EzSystems\EzPlatformGraphQL\Schema\Domain\Content\Mapper\FieldDefinition\ConfigurableFieldDefinitionMapper;
use EzSystems\EzPlatformGraphQL\Schema\Domain\Content\Worker\FieldDefinition\AddFieldDefinitionToDomainContentMutation;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Processes the deprecated inputType attribute of the ezplatform_graphql.fieldtype_input_handler service tag.
 * The value is added to the ezplatform_graphql.schema.content.mapping.field_definition_type, as the input_type
 * key for the tagged fieldtype.
 */
final class InputTypesMappingPass implements CompilerPassInterface
{
    private const INPUT_HANDLER_TAG = 'ezplatform_graphql.fieldtype_input_handler';
    private const INPUT_MAPPER_TAG = 'ezplatform_graphql.field_definition_input_mapper';
    private const INPUT_TYPE_ATTRIBUTE = 'InputType';
    private const FIELD_TYPE_ATTRIBUTE = 'fieldtype';
    private const PARAM = 'ezplatform_graphql.schema.content.mapping.field_definition_type';

    public function process(ContainerBuilder $container)
    {
        $mappingConfiguration = $container->getParameter('ezplatform_graphql.schema.content.mapping.field_definition_type');
        foreach ($container->findTaggedServiceIds(self::INPUT_HANDLER_TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['fieldtype'])) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            "The %s tag requires a 'fieldtype' property set to the Field Type's identifier",
                            self::FIELD_TYPE_ATTRIBUTE
                        )
                    );
                }

                if (isset($tag['inputType'])) {
                    @trigger_error(
                        sprintf(
                            'The %s tag\'s inputType attribute on %s is deprecated, and won\'t work anymore in ezplatform-graphql 2.0. Use the %s container parameter.',
                            self::INPUT_HANDLER_TAG,
                            $id,
                            self::PARAM
                        ),

                        E_USER_DEPRECATED
                    );
                    $mappingConfiguration[$tag['fieldtype']]['input_type'] = $tag['inputType'];
                }
            }
        }

        $container->setParameter('ezplatform_graphql.schema.content.mapping.field_definition_type', $mappingConfiguration);

        $configurableMapperDefinition = $container->getDefinition(ConfigurableFieldDefinitionMapper::class);
        foreach ($mappingConfiguration as $fieldtype => $configuration) {
            if (isset($configuration['input_type'])) {
                $configurableMapperDefinition->addTag(
                    self::INPUT_MAPPER_TAG,
                    [
                        'fieldtype' => $fieldtype
                    ]
                );
            }
        }
    }
}