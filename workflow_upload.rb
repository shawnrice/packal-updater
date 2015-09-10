require 'rest-client'
require 'base64'
require 'json'
require 'zip'
require 'pp'

class Packal
	def self.queue(json)
		begin
			puts JSON.parse(json)
			Packal.send(JSON.parse(json))
		rescue StandardError => e
			e.message
		end
	end

	def self.submit(params)
		type = ('workflow_revision' == params.keys[0].to_s) ? 'workflow' : params.keys[0]
		RestClient::Request.execute(:url => "http://localhost:3000/api/v1/alfred2/#{type}/submit", :payload => params, :method => :post)
	end

	def self.send(params)
		key = params.keys[0]
		['username', 'password'].each do |k|
			unless (params.key? k) then
				raise StandardError.new("Error: you need to pass a #{k} to submit a #{key}.")
			end
		end
		Packal::const_get(key.capitalize).submit(params)
	end

	class Report
		def self.submit(params)
			Packal.ensure_keys(params, ['workflow_revision_id', 'report_type', 'message'])
			Packal.submit(params)
		end
	end
	class Theme
		def self.submit(params)
			Packal.ensure_keys(params, ['name', 'description', 'uri'])
			Packal.submit(params)
		end
	end

	class Workflow
		def self.submit(params)
			Packal.ensure_keys(params, ['file', 'version'])
			file = ensure_file(params)
			check_mime_type(file)
			data = File.new(file, 'rb')
			Packal.submit(fix_keys(params, data))
		end
		private
			def self.ensure_file(params)
				if ! File.exist?(params['workflow']['file']) then
					raise StandardError.new("Error: file #{params['workflow']['file']} not found.")
				end
				params['workflow']['file']
			end
			def self.check_mime_type(file)
				unless ('zip' == `file --mime -b "#{file}"`.split("/")[1].split(';')[0])
					raise StandardError.new('Error: workflow file is not a valid archive.')
				end
			end
			def self.fix_keys(params, data)
				params['workflow_revision'] = { :file => data, :version => params['workflow']['version'] }
				params.delete('workflow')
				keys = params.keys.reverse.map(&:to_sym)
				params = params.to_a.reverse.map{|x| x[1]}
				Hash[keys.zip(params)]
			end
	end

	private
		def self.ensure_keys(params, keys)
			key = params.keys[0]
			keys.each do |k|
				unless (params[key].key? k) then
					raise StandardError.new("Error: you need to pass a #{k} to submit a #{key}.")
				end
			end
			true
		end
end

if ( ARGV[0].nil? ) then
	puts "You cannot run this script without passing a JSON argument to it."
	abort
end

object = JSON.parse(ARGV[0])

json = {
	:workflow => {:file => object['file'], :version => object['version']},
	:username => object['username'],
	:password => object['password']
}
puts json.inspect
output = Packal.queue(json.to_json)
begin
	json = JSON.parse(output)
	if json['code'] == 400 || json['code'] == 500 then
		pp file
		pp json
	end
rescue
	puts output.inspect
end