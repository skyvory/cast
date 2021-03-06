<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Resource extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('common','',TRUE);
		$this->load->helper('url');
		if($this->session->userdata('user_auth')) {
			$user = $this->session->userdata('user_auth');
			if($user['perm'] == 1) {
				redirect('admin', 'refresh');
			}
		} 
		else {
			redirect('login', 'refresh');
		}
	}
	function manage() {
		$this->load->helper('form');
		$this->load->helper('url');
		
		$proj = $this->session->userdata('active_project');
		if($proj) {
			$head['title'] = "Resource";
			$self['user'] = $this->session->userdata('user_auth');
			$data['project'] = $proj;

			$this->load->view('resource_head', $head);
			$this->load->view('menu_view', $self);
			$this->load->view('resource_view', $data);
			$this->load->view('foot');
		}
		else {
			redirect('project', 'location');
		}
	}
	public function do_upload() {
		$this->load->helper('url');

		$user = $this->session->userdata('user_auth');
		$proj = $this->session->userdata('active_project');
		$resource_type = $this->input->post('restype');
		$path_to_project = 'resources/' . $user['id'] . '/' . $proj['id'] . '/';

		// sprite type resource
		if($resource_type == 1) {
			$upload_path_url = base_url() . $path_to_project . 'sprite/';
			$config['upload_path'] = FCPATH . $path_to_project . 'sprite/';
			$config['allowed_types'] = 'jpg|jpeg|png|gif';
			$config['overwrite'] = FALSE;
			$config['max_size'] = 20 * 1024 * 1024; //20 MB
			$config['encrypt_name'] = TRUE;
			$this->load->library('upload', $config);

			if($this->upload->do_upload() == FALSE){
				$info = new StdClass;
				$info->error = $this->upload->display_errors();
				$files[] = $info;
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('files' => $files)));
			}
			else{
				$data = $this->upload->data();
				$config['source_image'] = $data['full_path'];
				$config['new_image'] =  FCPATH . $path_to_project . 'sprite/thumbs/';
				$config['maintain_ratio'] = TRUE;
				$config['create_thumb'] = TRUE;
				$config['thumb_marker'] = '_thumb';
				$config['quality'] = '70%';
				$config['width'] = 360;
				$config['height'] = 360;
				$this->load->library('image_lib', $config);
				//resize original image for thumbnail
				if($this->image_lib->resize() == FALSE){
					// $this->fb->log($this->image_lib->display_errors());
					$info = new StdClass;
					$info->error = $this->image_lib->display_errors();
					$files[] = $info;
					$this->output->set_content_type('application/json');
					$this->output->set_output(json_encode(array('files' => $files)));
				}
				else {
					//extract original filename without extension
					$base_file_name = $this->trimExtension($data['orig_name']);
					//get post data for character name and figure name
					$character_name = $this->input->post('charname');
					$figure_name = $this->input->post('figname');
					// $this->fb->log($character_name);
					//write file record to database
					$pass = $this->common->createSpriteResource($base_file_name, $data['file_name'], $character_name, $figure_name, '1', $proj['id']);
					if($pass != NULL) {
						$info = new StdClass;
						$info->id = $pass;
						$info->name = $base_file_name;
						$info->size = $data['file_size'];
						$info->type = $data['file_type'];
						//generate url info with url to thumb file
						$info->url = $upload_path_url . 'thumbs/' . $data['raw_name'] . '_thumb' . $data['file_ext'];
						$info->error = null;
						$info->resource_type = $resource_type;
						$info->character_name = $character_name;
						$info->figure_name = $figure_name;
						$files[] = $info;
						$this->output->set_content_type('application/json');
						$this->output->set_output(json_encode(array("files" => $files)));
					}
				}
			}
		}
		// background type resource
		else if($resource_type == 2) {
			$upload_path_url = base_url() . $path_to_project . 'background/';
			$config['upload_path'] = FCPATH . $path_to_project . 'background/';
			$config['allowed_types'] = 'jpg|jpeg|png';
			$config['overwrite'] = FALSE;
			$config['max_size'] = 20 * 1024 * 1024;
			$config['encrypt_name'] = TRUE;
			$config['remove_spaces'] = TRUE;
			$this->load->library('upload', $config);

			if($this->upload->do_upload() == FALSE){
				$info = new StdClass;
				$info->error = $this->upload->display_errors();
				$files[] = $info;
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('files' => $files)));
			}
			else{
				$this->fb->log($user['id']);
				$this->fb->log($proj['id']);
				$data = $this->upload->data();
				//config for image resize
				// $config['image_library'] = 'ImageMagick';
				$config['source_image'] = $data['full_path'];
				$config['new_image'] =  FCPATH . $path_to_project . 'background/thumbs/';
				$config['maintain_ratio'] = TRUE;
				$config['create_thumb'] = TRUE;
				$config['thumb_marker'] = '_thumb';
				$config['quality'] = '70%';
				$config['width'] = 360;
				$config['height'] = 360;
				$this->load->library('image_lib', $config);
				//resize original image for thumbnail
				if($this->image_lib->resize() == FALSE){
					$info = new StdClass;
					$info->error = $this->image_lib->display_errors();
					$files[] = $info;
					$this->output->set_content_type('application/json');
					$this->output->set_output(json_encode(array('files' => $files)));
				}
				else {
					//trim original filename
					$base_file_name = $this->trimExtension($data['orig_name']);
					//write file info to database
					$pass = $this->common->createBackgroundResource($base_file_name, $data['file_name'], '2', $proj['id']);
					if($pass != NULL) {
						$info = new StdClass;
						$info->id = $pass;
						$info->name = $base_file_name;
						$info->size = $data['file_size'];
						$info->type = $data['file_type'];
						// $info->url = $upload_path_url . $data['file_name'];
						$info->url = $upload_path_url . 'thumbs/' . $data['raw_name'] . '_thumb' . $data['file_ext'];
						$info->error = null;
						//unnecessary!$info->delete_type = 'DELETE';
						$info->resource_type = $resource_type;
						$files[] = $info;
						$this->output->set_content_type('application/json')->set_output(json_encode(array("files" => $files)));
					}
				}
			}
		}
		// bgm type resource
		else if($resource_type == 3) {
			//$this->load->library('UploadHandler');
			$upload_path_url = base_url() . $path_to_project . 'bgm/';
			$config['upload_path'] = FCPATH . $path_to_project . 'bgm/';
			$config['allowed_types'] = 'mp3|ogg';
			$config['max_size'] = 30 * 1024 * 1024;
			$config['encrypt_name'] = TRUE;
			$this->load->library('upload', $config);
			if($this->upload->do_upload() == FALSE){
				$info = new StdClass;
				$info->error = $this->upload->display_errors();
				$files[] = $info;
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('files' => $files)));
			}
			else{
				$data = $this->upload->data();
				$base_file_name = $this->trimExtension($data['orig_name']);
				$pass = $this->common->createAudioResource($base_file_name, $data['file_name'], '3', $proj['id']);
				if($pass != NULL) {
					$info = new StdClass;
					$info->id = $pass;
					$info->name = $base_file_name;
					$info->size = $data['file_size'];
					$info->type = $data['file_type'];
					$info->url = $upload_path_url . $data['file_name'];
					$info->error = null;
					$info->resource_type = $resource_type;
					$files[] = $info;
					$this->output->set_content_type('application/json')->set_output(json_encode(array("files" => $files)));
				}
			}
		}
		// sfx type resource
		else if($resource_type == 4) {
			$upload_path_url = base_url() . $path_to_project . 'sfx/';
			$config['upload_path'] = FCPATH . $path_to_project . 'sfx/';
			$config['allowed_types'] = 'mp3|ogg'
;			$config['max_size'] = 30 * 1024 * 1024;
			$config['encrypt_name'] = TRUE;
			$this->load->library('upload', $config);

			if($this->upload->do_upload() == FALSE){
				$this->fb->log($this->upload->display_errors());
			}
			else{
				$data = $this->upload->data();
				$base_file_name = $this->trimExtension($data['orig_name']);
				$pass = $this->common->createAudioResource($base_file_name, $data['file_name'], '4', $proj['id']);
				if($pass != NULL) {
					$info = new StdClass;
					$info->id = $pass;
					$info->name = $base_file_name;
					$info->size = $data['file_size'];
					$info->type = $data['file_type'];
					$info->url = $upload_path_url . $data['file_name'];
					$info->error = null;
					$info->resource_type = $resource_type;
					$files[] = $info;
					$this->output->set_content_type('application/json')->set_output(json_encode(array("files" => $files)));
				}
			}
		}
		// voice type resource
		else if($resource_type == 5) {
			//$this->load->library('UploadHandler');
			$upload_path_url = base_url() . $path_to_project . 'voice/';
			$config['upload_path'] = FCPATH . $path_to_project . 'voice/';
			$config['allowed_types'] = 'mp3|ogg';
			$config['max_size'] = 30 * 1024 * 1024;
			$config['encrypt_name'] = TRUE;
			$this->load->library('upload', $config);

			if($this->upload->do_upload() == FALSE){
				$info = new StdClass;
				$info->error = $this->upload->display_errors();
				$files[] = $info;
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('files' => $files)));
			}
			else{
				$data = $this->upload->data();
				$base_file_name = $this->trimExtension($data['orig_name']);
				//get post data for character name, input to database only for recognition
				// $character_name = $this->input->post('charname');
				$pass = $this->common->createVoiceResource($base_file_name, $data['file_name'], '5', $proj['id']);
				if($pass != NULL) {
					$info = new StdClass;
					$info->id = $pass;
					$info->name = $base_file_name;
					$info->size = $data['file_size'];
					$info->type = $data['file_type'];
					$info->url = $upload_path_url . $data['file_name'];
					$info->error = null;
					$info->resource_type = $resource_type;
					// $info->character_name = $character_name;
					$files[] = $info;
					$this->output->set_content_type('application/json')->set_output(json_encode(array("files" => $files)));
				}
			}
		}
		else if($resource_type == 6) {
			//$this->load->library('UploadHandler');
			$upload_path_url = base_url() . $path_to_project . 'video/';
			$config['upload_path'] = FCPATH . $path_to_project . 'video/';
			$config['allowed_types'] = 'mp4';
			$config['max_size'] = 160 * 1024 * 1024;
			$config['encrypt_name'] = TRUE;
			$this->load->library('upload', $config);

			if($this->upload->do_upload() == FALSE){
				$info = new StdClass;
				$info->error = $this->upload->display_errors();
				$files[] = $info;
				$this->output->set_content_type('application/json');
				$this->output->set_output(json_encode(array('files' => $files)));
			}
			else{
				$data = $this->upload->data();
				$base_file_name = $this->trimExtension($data['orig_name']);
				$pass = $this->common->createVideoResource($base_file_name, $data['file_name'], '6', $proj['id']);
				if($pass != NULL) {
					$info = new StdClass;
					$info->id = $pass;
					$info->name = $base_file_name;
					$info->size = $data['file_size'];
					$info->type = $data['file_type'];
					$info->url = $upload_path_url . $data['file_name'];
					$info->error = null;
					$info->resource_type = $resource_type;
					$files[] = $info;
					$this->output->set_content_type('application/json')->set_output(json_encode(array("files" => $files)));
				}
			}
		}
	}
	private function trimExtension($filestring) {
		$string = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filestring);
		return $string;
	}
	public function changeBackgroundProperty() {
		$resource_id = $this->input->post('id');
		$name = $this->input->post('name');
		$proj = $this->session->userdata('active_project');
		if($proj) {
			$pass = $this->common->updateBackgroundResource($proj['id'], $resource_id, $name);
			echo "1";
		}
		else {
			echo "0";
		}
	}
	public function changeSpriteProperty() {
		$resource_id = $this->input->post('id');
		$character_name = $this->input->post('character');
		$figure_name = $this->input->post('figure');
		$expression_name = $this->input->post('expression');
		$proj = $this->session->userdata('active_project');
		if($proj) {
			$pass = $this->common->updateSpriteResource($proj['id'], $resource_id, $character_name, $figure_name, $expression_name);
			echo "1";
		}
		else {
			echo "0";
		}
	}
	public function loadResource() {
		$user = $this->session->userdata('user_auth');
		$proj = $this->session->userdata('active_project');
		$resource_type_request = $this->input->post('type');
		$path_to_project = base_url() . 'resources/' . $user['id'] . '/' . $proj['id'] . '/';
		if($resource_type_request == 1) {
			$resource_data = $this->common->getSpriteResource($proj['id']);
			foreach ($resource_data as $value) {
				?>
					<div class="media sprite-media">
						<div class="media-left sprite-thumbnail-area">
							<img src="<?php echo $path_to_project . 'sprite/thumbs/' . $this->trimExtension($value['file_name']) . '_thumb' . $this->extractExtension($value['file_name']); ?>" class="media-object resource-thumbnail" />
						</div>
						<div class="media-body">
							<div class="resource-property">
								<form class="form-horizontal sprite-form">
									<div class="form-group">
										<label for="spritecharactername_<?php echo $value['resource_id']; ?>" class="col-sm-3 control-label">Character Name</label>
										<div class="col-sm-5">
										<input type="text" id="spritecharactername_<?php echo $value['resource_id']; ?>" name="sprite_character" class="form-control input-sm" value="<?php echo $value['character_name']; ?>" />
										</div>
									</div>
									<div class="form-group">
										<label for="spritefigurename_<?php echo $value['resource_id']; ?>" class="col-sm-3 control-label">Pose Name</label>
										<div class="col-sm-5">
										<input type="text" id="spritefigurename_<?php echo $value['resource_id']; ?>" name="sprite_figure" class="form-control input-sm" value="<?php echo $value['figure_name']; ?>" />
										</div>
									</div>
									<div class="form-group">
										<label for="spriteexpressionname_<?php echo $value['resource_id']; ?>" class="col-sm-3 control-label">Expression Name</label>
										<div class="col-sm-5">
										<input type="text" id="spriteexpressionname_<?php echo $value['resource_id']; ?>" name="sprite_expression" class="form-control input-sm" value="<?php echo $value['expression_name']; ?>"/>
										</div>
									</div>
									<input type="hidden" id="spriteid_<?php echo $value['resource_id']; ?>" name="sprite_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary sprite-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger sprite-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
				<?php
			}
		}
		if($resource_type_request == 2) {
			$resource_data = $this->common->getProjectResourceByType($proj['id'], 2);
			foreach ($resource_data as $value) {
				?>
					<div class="media background-media">
						<div class="media-left background-thumbnail-area">
							<img src="<?php echo $path_to_project . 'background/thumbs/' . $this->trimExtension($value['file_name']) . '_thumb' . $this->extractExtension($value['file_name']); ?>" class="media-object resource-thumbnail"/>
						</div>
						<div class="media-body">
							<div class="resource-property">
								<form class="form-inline">
									<div class="form-group background-form">
										<label for="backgroundname_<?php echo $value['resource_id']; ?>">Name &nbsp;</label>
										<input type="text" id="backgroundname_<?php echo $value['resource_id']; ?>" name="background_name" class="form-control input-sm" value="<?php echo $value['name']; ?>" />
									</div>
									<input type="hidden" id="backgroundid_<?php echo $value['resource_id']; ?>" name="background_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary background-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger background-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
				<?php
			}
		}
		else if($resource_type_request == 3) {
			$resource_data = $this->common->getProjectResourceByType($proj['id'], '3');
			echo '<table class="table"><tbody>';
			foreach ($resource_data as $value) {
				?>
					<tr><td>
					<div class="media bgm-media">
						<div class="media-left audio-thumbnail-area">
							<img src="<?php echo base_url(); ?>assets/images/musical_note-512.png" class="media-object resource-thumbnail"/>
						</div>
						<div class="media-body">
							<div class="resource-property">
								<div class="audio-player-area">
									<audio controls  preload="none">
										<source src="<?php echo $path_to_project . 'bgm/' . $value['file_name']; ?>">
									</audio>
								</div>
								<form class="form-inline audio-inline-form">
									<div class="form-group bgm-form">
										<label for="bgmname_<?php echo $value['resource_id']; ?>">Name &nbsp;</label>
										<input type="text" id="bgmname_<?php echo $value['resource_id']; ?>" name="bgm_name" class="form-control input-xs" value="<?php echo $value['name']; ?>" />
									</div>
									<input type="hidden" id="bgmid_<?php echo $value['resource_id']; ?>" name="bgm_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary bgm-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger bgm-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
					</td></tr>
				<?php
			}
			echo '</tbody></table>';
		}
		else if($resource_type_request == 4) {
			$resource_data = $this->common->getProjectResourceByType($proj['id'], '4');
			echo '<table class="table"><tbody>';
			foreach ($resource_data as $value) {
				?>
					<tr><td>
					<div class="media sfx-media">
						<div class="media-left audio-thumbnail-area">
							<img src="<?php echo base_url(); ?>assets/images/Audio-512.png" class="media-object resource-thumbnail"/>
						</div>
						<div class="media-body">
							<div class="resource-property">
								<div class="audio-player-area">
									<audio controls preload="none">
										<source src="<?php echo $path_to_project . 'sfx/' . $value['file_name']; ?>">
									</audio>
								</div>
								<form class="form-inline audio-inline-form">
									<div class="form-group sfx-form">
										<label for="sfxname_<?php echo $value['resource_id']; ?>">Name &nbsp;</label>
										<input type="text" id="sfxname_<?php echo $value['resource_id']; ?>" name="sfx_name" class="form-control input-xs" value="<?php echo $value['name']; ?>" />
									</div>
									<input type="hidden" id="sfxid_<?php echo $value['resource_id']; ?>" name="sfx_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary sfx-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger sfx-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
					</td></tr>
				<?php
			}
			echo '</tbody></table>';
		}
		else if($resource_type_request == 5) {
			$resource_data = $this->common->getProjectResourceByType($proj['id'], '5');
			echo '<table class="table"><tbody>';
			foreach ($resource_data as $value) {
				?>
					<tr><td>
					<div class="media voice-media">
						<div class="media-left audio-thumbnail-area">
							<img src="<?php echo base_url(); ?>assets/images/microphone-2-512.png" class="media-object resource-thumbnail"/>
						</div>
						<div class="media-body">
							<div class="resource-property">
								<div class="audio-player-area">
									<audio controls preload="none">
										<source src="<?php echo $path_to_project . 'voice/' . $value['file_name']; ?>">
									</audio>
								</div>
								<form class="form-inline audio-inline-form">
									<div class="form-group">
										<label for="voicename_<?php echo $value['resource_id']; ?>">Name &nbsp;</label>
										<input type="text" id="voicename_<?php echo $value['resource_id']; ?>" name="voice_name" class="form-control input-xs" value="<?php echo $value['name']; ?>" />
									</div>
									<input type="hidden" id="voiceid_<?php echo $value['resource_id']; ?>" name="voice_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary voice-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger voice-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
					</td></tr>
				<?php
			}
			echo '</tbody></table>';
		}
								 /*
									<div class="form-group">
										<label for="voicecharactername_<?php echo $value['resource_id']; ?>">Character Name &nbsp;</label>
										<input type="text" id="voicecharactername_<?php echo $value['resource_id']; ?>" name="voice_character" class="form-control input-xs" value="<?php echo $value['character_name']; ?>" />
									</div>
*/
		else if($resource_type_request == 6) {
			$resource_data = $this->common->getProjectResourceByType($proj['id'], '6');
			echo '<table class="table"><tbody>';
			foreach ($resource_data as $value) {
				?>
					<tr><td>
					<div class="media video-media">
						<div class="media-left video-player-area">
							<video controls width="300" height="225" class="video-player" preload="none">
								<source src="<?php echo $path_to_project . 'video/' . $value['file_name']; ?>">
							</video>
						</div>
						<div class="media-body media-middle">
							<div class="resource-property">
								<form class="form-inline">
									<div class="form-group">
										<label for="videoname_<?php echo $value['resource_id']; ?>">Name &nbsp;</label>
										<input type="text" id="videoname_<?php echo $value['resource_id']; ?>" name="video_name" class="form-control input-sm" value="<?php echo $value['name']; ?>" />
									</div>
									<input type="hidden" id="videoid_<?php echo $value['resource_id']; ?>" name="video_id" value="<?php echo $value['resource_id']; ?>" />
									<button type="button" class="btn btn-primary video-form-submit-button">Change</button>
									<button type="button" class="btn btn-danger video-form-delete-button">Delete</button>
								</form>
							</div>
						</div>
					</div>
					</td></tr>
				<?php
			}
			echo '</tbody></table>';
		}
	}
	private function extractExtension($filestring) {
		$string = '.' . preg_replace('/^.*\.([^.]+)$/D', '$1', $filestring);
		return $string;
	}
	public function removeResource() {
		$resource_id = $this->input->post('id');
		$user = $this->session->userdata('user_auth');
		$proj = $this->session->userdata('active_project');
		$file = $this->common->getResource($proj['id'], $resource_id);
		$del = $this->common->deleteResource($proj['id'], $resource_id);
		if($del) {
			$path_to_project = 'resources/' . $user['id'] . '/' . $proj['id'] . '/';
			switch ($file['fk_resourcetype_id']) {
				case 1:
					$directory_type = "sprite";
					break;
				case 2:
					$directory_type = "background";
					break;
				case 3:
					$directory_type = "bgm";
					break;
				case 4:
					$directory_type = "sfx";
					break;
				case 5:
					$directory_type = "voice";
					break;
				case 6:
					$directory_type = "video";
					break;
			}
			$select_file = $path_to_project . $directory_type . '/' . $file['file_name'];
			unlink($select_file);
			$image_type = array(1,2);
			if(in_array($file['fk_resourcetype_id'], $image_type)) {
				$select_file_thumb = $path_to_project . $directory_type . '/thumbs/' . $this->trimExtension($file['file_name']) . '_thumb' . $this->extractExtension($file['file_name']);
				unlink($select_file_thumb);
			}
			echo "1";
		}
		else {
			echo "0";
		}
		
	}
	public function changeAudioVideoProperty() {
		$resource_id = $this->input->post('id');
		$name = $this->input->post('name');
		$proj = $this->session->userdata('active_project');
		if($proj) {
			$pass = $this->common->updateAudioVideoResource($proj['id'], $resource_id, $name);
			echo "1";
		}
		else {
			echo "0";
		}
	}
}

?>