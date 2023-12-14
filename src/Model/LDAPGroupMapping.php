<?php

namespace SilverStripe\LDAP\Model;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\LDAP\Services\LDAPService;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;

/**
 * Class LDAPGroupMapping
 *
 * An individual mapping of an LDAP group to a SilverStripe {@link Group}
 * @method Group Group()
 */
class LDAPGroupMapping extends DataObject
{
    /**
     * {@inheritDoc}
     * @var string
     */
    private static $table_name = 'LDAPGroupMapping';

    /**
     * @var array
     */
    private static $db = [
        'DN' => 'Text', // the DN value of the LDAP object in AD, e.g. CN=Users,DN=playpen,DN=local
        'Scope' => 'Enum("Subtree,OneLevel","Subtree")' // the scope of the mapping
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Group' => Group::class
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'DN'
    ];

    /**
     * @var array
     */
    private static $dependencies = [
        'ldapService' => '%$' . LDAPService::class,
    ];

    private static $singular_name = 'LDAP Group Mapping';

    private static $plural_name = 'LDAP Group Mappings';

    /**
     * {@inheritDoc}
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('DN');

        $field = DropdownField::create('DN', _t(__CLASS__ . '.LDAPGROUP', 'LDAP Group'));
        $field->setEmptyString(_t(__CLASS__ . '.SELECTONE', 'Select one'));
        $groups = $this->ldapService->getGroups(true, ['dn', 'name']);
        $source = [];
        if ($groups) {
            foreach ($groups as $dn => $record) {
                $source[$dn] = sprintf('%s (%s)', $record['name'], $dn);
            }
        }
        asort($source);
        $field->setSource($source);
        $fields->addFieldToTab('Root.Main', $field);

        $fields->removeByName('Scope');
        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create('Scope', _t(__CLASS__ . '.SCOPE', 'Scope'), [
                'Subtree' => _t(
                    __CLASS__ . '.SUBTREE_DESCRIPTION',
                    'Users within this group and all nested groups within'
                ),
                'OneLevel' => _t(__CLASS__ . '.ONELEVEL_DESCRIPTION', 'Only users within this group'),
            ])
        );

        return $fields;
    }
}
