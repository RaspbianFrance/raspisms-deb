<?php

/*
 * This file is part of RaspiSMS.
 *
 * (c) Pierre-Lin Bonnemaison <plebwebsas@gmail.com>
 *
 * This source file is subject to the GPL-3.0 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace models;

    class Group extends StandardModel
    {
        /**
         * Return a list of groups for a user.
         * Add a column nb_contacts.
         *
         * @param int  $id_user : user id
         * @param ?int $limit   : Number of entry to return or null
         * @param ?int $offset  : Number of entry to ignore or null
         *
         * @return array
         */
        public function list_for_user(int $id_user, $limit, $offset)
        {
            $query = '
                SELECT g.*, COUNT(gc.id) as nb_contact
                FROM `group` as g
                LEFT JOIN group_contact as gc
                ON g.id = gc.id_group
                WHERE id_user = :id_user
                GROUP BY g.id
            ';

            if (null !== $limit)
            {
                $limit = (int) $limit;

                $query .= ' LIMIT ' . $limit;
                if (null !== $offset)
                {
                    $offset = (int) $offset;
                    $query .= ' OFFSET ' . $offset;
                }
            }

            $params = [
                'id_user' => $id_user,
            ];

            return $this->_run_query($query, $params);
        }

        /**
         * Return a group by his name for a user.
         *
         * @param int    $id_user : User id
         * @param string $name    : Group name
         *
         * @return array
         */
        public function get_by_name_for_user(int $id_user, string $name)
        {
            return $this->_select_one($this->get_table_name(), ['id_user' => $id_user, 'name' => $name]);
        }

        /**
         * Delete relations between group and contacts for a group.
         *
         * @param int $id_group : Group id
         *
         * @return int : Number of deleted rows
         */
        public function delete_group_contact_relations(int $id_group)
        {
            return $this->_delete('group_contact', ['id_group' => $id_group]);
        }

        /**
         * Insert a relation between a group and a contact.
         *
         * @param int $id_group   : Group id
         * @param int $id_contact : Contact id
         *
         * @return mixed (bool|int) : False on error, new row id else
         */
        public function insert_group_contact_relation(int $id_group, int $id_contact)
        {
            $success = (bool) $this->_insert('group_contact', ['id_group' => $id_group, 'id_contact' => $id_contact]);

            return $success ? $this->_last_id() : false;
        }

        /**
         * Get groups contacts.
         *
         * @param int $id_group : Group id
         *
         * @return array : Contacts of the group
         */
        public function get_contacts(int $id_group)
        {
            $query = '
                SELECT *
                FROM `contact`
                WHERE id IN (SELECT id_contact FROM `group_contact` WHERE id_group = :id_group)
            ';

            $params = ['id_group' => $id_group];

            return $this->_run_query($query, $params);
        }

        /**
         * Return table name.
         */
        protected function get_table_name(): string
        {
            return 'group';
        }
    }
