<?php
/**
 * This file is part of the Ikarus Framework.
 * The Ikarus Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * The Ikarus Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with the Ikarus Framework. If not, see <http://www.gnu.org/licenses/>.
 */
namespace ikarus\system\auth;

/**
 * Defines default methods for user objects.
 * @author                    Johannes Donath
 * @copyright                 2012 Evil-Co.de
 * @package                   de.ikarus-framework.core
 * @subpackage                system
 * @category                  Ikarus Framework
 * @license                   GNU Lesser Public License <http://www.gnu.org/licenses/lgpl.txt>
 * @version                   2.0.0-0001
 */
interface IUserObject {

	/**
	 * Returns user's GUID (Basically a string value which isn't longer than 36 chars. See ikarus\util.GUID::FORMAT for a default format definition).
	 * @return                        string(36)
	 * @api
	 */
	public function getUserID ();

	/**
	 * Returns user's human readable identifier (Usually a mail address or an alias (username)).
	 * @return                        string
	 * @api
	 */
	public function getHumanReadableIdentifier ();

	/**
	 * Registers a new extension.
	 * @param                        string $extension
	 * @return                        void
	 * @api
	 */
	public function registerExtension ($extensionClass);

	/**
	 * Unregisters an extension.
	 * @param                        IUserObjectExtension $extension
	 * @return                        void
	 * @api
	 */
	public function unregisterExtension (IUserObjectExtension $extension);

	/**
	 * Forwards update() calls to all extensions.
	 * @return                        void
	 * @api
	 */
	public function update ();
}

?>