import flash.net.FileReference;

class FileItem
{
	private static var file_id_sequence:Number = 0;		// tracks the file id sequence

	private var postObject:Object;
	public var file_reference:FileReference;
	public var id:String;
	public var index:Number = -1;
	public var file_status:Number = 0;
	private var js_object:Object;
	
	public static var FILE_STATUS_QUEUED:Number			= -1;
	public static var FILE_STATUS_IN_PROGRESS:Number	= -2;
	public static var FILE_STATUS_ERROR:Number			= -3;
	public static var FILE_STATUS_SUCCESS:Number		= -4;
	public static var FILE_STATUS_CANCELLED:Number		= -5;
	public static var FILE_STATUS_NEW:Number			= -6;	// This file status should never be sent to JavaScript
	
	public function FileItem(file_reference:FileReference, control_id:String, index:Number)
	{
		this.postObject = {};
		this.file_reference = file_reference;
		this.id = control_id + "_" + (FileItem.file_id_sequence++);
		this.file_status = FileItem.FILE_STATUS_QUEUED;
		this.index = index;
		
		this.js_object = {
			id: this.id,
			index: this.index,
			name: this.file_reference.name,
			size: this.file_reference.size,
			type: this.file_reference.type || "",
			creationdate: this.file_reference.creationDate || new Date(0),
			modificationdate: this.file_reference.modificationDate || new Date(0),
			filestatus: this.file_status,
			post: this.GetPostObject()
		};
		
	}
	
	public function AddParam(name:String, value:String):Void {
		this.postObject[name] = value;
	}
	
	public function RemoveParam(name:String):Void {
		delete this.postObject[name];
	}
	
	public function GetPostObject(escape:Boolean):Object {
		if (escape) {
			var escapedPostObject:Object = { };
			for (var k:String in this.postObject) {
				if (this.postObject.hasOwnProperty(k)) {
					var escapedName:String = FileItem.EscapeParamName(k);
					escapedPostObject[escapedName] = this.postObject[k];
				}
			}
			return escapedPostObject;
		} else {
			return this.postObject;
		}
	}
	
	// Update the js_object and return it.
	public function ToJavaScriptObject():Object {
		this.js_object.filestatus = this.file_status;
		this.js_object.post = this.GetPostObject(true);
		
		return this.js_object;
	}
	
	public function toString():String {
		return "FileItem - ID: " + this.id;
	}

	/*
	// The purpose of this function is to escape the property names so when Flash
	// passes them back to javascript they can be interpretted correctly.
	// ***They have to be unescaped again by JavaScript.**
	//
	// This works around a bug where Flash sends objects this way:
	//		object.parametername = "value";
	// instead of
	//		object["parametername"] = "value";
	// This can be a problem if the parameter name has characters that are not
	// allowed in JavaScript identifiers:
	// 		object.parameter.name! = "value";
	// does not work but,
	//		object["parameter.name!"] = "value";
	// would have worked.
	*/
	public static function EscapeParamName(name:String):String {
		var valid_chars:String = "0123456789abcdefghigklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ_";
		var numerics:String = "0123456789";

		for (var i:Number = name.length - 1; i >= 0; i--) {
			var character:String = name.substr(i, 1);
			if (valid_chars.indexOf(character) < 0) {
				name = name.substr(0, i) + FileItem.EscapeCharacter(character) + (i < name.length ? name.substr(i + 1) : "");
			}
		}
		
		if (numerics.indexOf(name.substr(0, 1)) > -1) {
			name = FileItem.EscapeCharacter(name.substr(0, 1)) + name.substr(1);
		}
		
		return name;
	}
	public static function EscapeCharacter(character:String):String {
		return "$" + ("0000" + character.charCodeAt(0).toString(16)).substr(-4);
	}	
}