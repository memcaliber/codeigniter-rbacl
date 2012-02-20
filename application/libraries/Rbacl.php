<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class RBACL
{
	private		$db;
	protected	$userID		= 0;
	protected	$userInfo	= FALSE;
	protected	$userRoles	= array();
	protected	$userPerms	= array();
	
	public function __construct($userID=FALSE)
	{
		// load an instance of the database class
		$this->db	=& DB('default',TRUE);
		// 
		if ($userID && is_numeric($userID) && ($userID>0))
		{
			$this->set_user($userID);
		}
	}
	
	public function build_acl()
	{
		// first get the permissions associated with the role
		if (count($this->userRoles)>0)
		{
			$this->userPerms	= array_merge($this->userPerms, $this->get_role_perms($this->userRoles));
		}
		// then get the permissions associated with this specific user
		$this->userPerms	= array_merge($this->userPerms, $this->get_user_perms($this->userID));
	}
	
	public function set_user($userID)
	{
		// set this instance's userID
		$this->userID		= $userID;
		// set this instance's userInfo
		$this->db->where('id', $userID);
		$info	= $this->db->get('acl_user');
		$this->userInfo		= $info->first_row();
		// set this instance's userRoles
		$this->userRoles	= $this->get_user_roles($this->userID);
		// build the ACL for this user
		$this->build_acl();
	}
	
	/*	GETTER FUNCTIONS	*/
	
	public function get_all()
	{
		$result	= new stdClass();
		$result->id		= $this->userID;
		$result->info	= $this->userInfo;
		$result->roles	= $this->userRoles;
		$result->perms	= $this->userPerms;
		return $result;
	}
	
	public function get_user()
	{
		return $this->userID;
	}
	
	public function get_info($key=FALSE)
	{
		if ($key===FALSE)
		{
			return $this->userInfo;
		}
		elseif (is_string($key))
		{
			return isset($this->userInfo->$key) ? $this->userInfo->$key : FALSE;
		}
		return FALSE;
	}
	
	public function get_roles()
	{
		return $this->userRoles;
	}
	
	public function get_perms()
	{
		return $this->userPerms;
	}
	
	/*	CHECKER FUNCTIONS	*/
	
	public function has_role($roleID)
	{
		foreach ($this->userRoles as $k=>$v)
		{
			if ($v==$roleID)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public function has_permission($key)
	{
		$key	= strtolower($key);
		if (array_key_exists($key, $this->userPerms))
		{
			if ($this->userPerms[$key]->value)
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/*	ROLES FUNCTIONS	*/
	
	public function get_user_roles($userID)
	{
		if (empty($userID))
		{
			return array();
		}
		$this->db->where('user_id', $userID);
		$this->db->order_by('created_at', 'ASC');
		$list	= $this->db->get('acl_user_role');
		$roles	= array();
		foreach($list->result() as $item)
		{
			$roles[]	= (int) $item->role_id;
		}
		return $roles;
	}
	
	public function get_all_roles($full=FALSE)
	{
		$this->db->order_by('name', 'ASC');
		$list	= $this->db->get('acl_role');
		$roles	= array();
		foreach($list->result() as $item)
		{
			$roles[]	= $full ? $item : (int) $item->id;
		}
		return $roles;
	}
	
	/*	PERMISSIONS FUNCTIONS	*/
	
	public function get_user_perms($userID)
	{
		$this->db->select('acl_permission.id');
		$this->db->select('acl_permission.key');
		$this->db->select('acl_permission.name');
		$this->db->select('acl_user_permission.value');
		$this->db->join('acl_permission', 'acl_user_permission.permission_id=acl_permission.id', 'left');
		$this->db->where('acl_user_permission.user_id', $userID);
		$this->db->group_by('acl_user_permission.id');
		$list	= $this->db->get('acl_user_permission');
		$perms	= array();
		foreach($list->result() as $item)
		{
			if (!empty($item->name))
			{
				$item->value		= ($item->value==1);
				$item->inherited	= false;
				$perms[$item->key]	= $item;
			}
		}
		return $perms;
	}
	
	public function get_role_perms($userRoles)
	{
		$this->db->select('acl_permission.id');
		$this->db->select('acl_permission.key');
		$this->db->select('acl_permission.name');
		$this->db->select('acl_role_permission.value');
		$this->db->join('acl_permission', 'acl_role_permission.permission_id=acl_permission.id', 'left');
		if (is_array($userRoles))
		{
			$this->db->where_in('acl_role_permission.role_id', $userRoles);
		}
		else
		{
			$this->db->where('acl_role_permission.role_id', $userRoles);
		}
		$this->db->group_by('acl_role_permission.id');
		$list	= $this->db->get('acl_role_permission');
		$perms	= array();
		foreach($list->result() as $item)
		{
			if (!empty($item->name))
			{
				$item->value		= ($item->value==1);
				$item->inherited	= true;
				$perms[$item->key]	= $item;
			}
		}
		return $perms;
	}
	
	public function get_all_perms($full=FALSE)
	{
		$this->db->order_by('id', 'ASC');
		$list	= $this->db->get('acl_permission');
		$perms	= array();
		foreach($list->result() as $item)
		{
			$perms[]	= $full ? $item : (int) $item->id;
		}
		return $perms;
	}
}
