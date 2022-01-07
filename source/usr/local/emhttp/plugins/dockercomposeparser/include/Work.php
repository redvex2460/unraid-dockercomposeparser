<?php
	$extranetwork = $_POST["extranetwork"];
	$nwname = $_POST["nwname"];
	echo "$nwname";
	if($nwname == "")
		$nwname = "Composed";
	$data = $_POST["data"];
	file_put_contents("./plugins/redvex2460.dockercomposeparser/include/data.yml", $data);
	exec('cd ./plugins/redvex2460.dockercomposeparser/include && yq eval -o=j data.yml',$output);
	$content = implode($output);
	$contentAsJson = json_decode($content);
	$services = ((array)$contentAsJson->services);
	foreach($services as $service)
	{
		if($extranetwork == "Yes")
		{
			exec("docker network create -d bridge $nwname");
		}
		$network = "br0";
		if($extranetwork == "Yes")
		{
			$network = $nwname;
		}
		$service->network = $network;
		$service->nwname = $nwname;
		$name = array_search($service,$services);
		$service->name = "$name";
		$service->configfields = array();
		ParseHeader($service);
		ParseVolumes($service);
		ParsePorts($service);
		ParseEnvironment($service);
		ParseConfigFields($service);
		$service->xml = $service->xml."</Container>";
		file_put_contents("/boot/config/plugins/dockerMan/templates-user/my-$nwname-$name.xml", $service->xml);

	}
function ParseHeader($service)
{
	$service->xml = "<?xml version=\"1.0\"?>
<Container version=\"2\">
	<Name>{$service->nwname}-{$service->name}</Name>
	<Repository>$service->image</Repository>
	<Registry></Registry>
	<Network>$service->network</Network>
	<MyIP/>
	<Shell>sh</Shell>
	<Privileged>false</Privileged>
	<Support></Support>
	<Project></Project>
	<Overview>This Docker is made by RedVex2460 Docker Compose Parser</Overview>
	<Category></Category>
	<WebUI/>
	<TemplateURL/>
	<Icon></Icon>
	<ExtraParams></ExtraParams>
	<PostArgs/>
	<CPUset/>
	<DateInstalled></DateInstalled>
	<DonateText/>
	<DonateLink/>
	<Description>This Docker is made by RedVex2460 Docker Compose Parser</Description>
	";
}

function ParseVolumes($service)
{
	if(property_exists($service, "volumes"))
	{
		$service->xml = $service->xml."<Data>";
		foreach($service->volumes as $volume)
		{
			if(is_object($volume))
			{
					$service->xml = $service->xml."
<Volume>
	<HostDir></HostDir>
	<ContainerDir>$volume->target</ContainerDir>
	<Mode>rw</Mode>
</Volume>";
				array_push($service->configfields, (object) [
				"name" => $volume->target,
				"target" => $volume->target,
				"value" => "",
				"default" => "",
				"mode" => "rw",
				"description" => "Container Path: $volume->target",
				"type" => "Path",
				"display" => "",
				"required" => "false",
				"mask" => "false"
				]);
			}
			else
			{
				$volumes = explode(":",$volume);
				$service->xml = $service->xml."
<Volume>
	<HostDir></HostDir>
	<ContainerDir>$volumes[1]</ContainerDir>
	<Mode>rw</Mode>
</Volume>";
			
				array_push($service->configfields, (object) [
				"name" => $volumes[1],
				"target" => $volumes[1],
				"value" => "",
				"default" => "",
				"mode" => "rw",
				"description" => "Container Path: $volumes[1]",
				"type" => "Path",
				"display" => "",
				"required" => "false",
				"mask" => "false"
				]);
			}
		}
		$service->xml = $service->xml."</Data>";
	}
	
}
function ParsePorts($service)
{
	if(property_exists($service, "ports"))
	{
		$service->xml = $service->xml."<Publish>";
		foreach($service->ports as $port)
		{
			$ports = explode(":",$port);
			$obj = (object) [
				"name" => "Container Port $ports[0]",
				"target" => $ports[1],
				"value" => $ports[1],
				"default" => $ports[1],
				"mode" => "",
				"description" => "Container Port: $ports[1]
				Default Value: $ports[1]",
				"type" => "Port",
				"display" => "",
				"required" => "false",
				"mask" => "false"
			];
			array_push($service->configfields, $obj);
			$service->xml = $service->xml."
<Port>
	<HostPort></HostPort>
	<ContainerPort>$ports[1]</ContainerPort>
	<Protocol>tcp</Protocol>
</Port>
			";
		}
		$service->xml = $service->xml."</Publish>";
	}
}
function ParseEnvironment($service)
{
	if(property_exists($service, "environment"))
	{
		$service->xml = $service->xml."<Environment>";
		foreach($service->environment as $name => $value)
		{	
			array_push($service->configfields, (object) [
				"name" => $name,
				"target" => $name,
				"value" => $value,
				"default" => $value,
				"mode" => "",
				"description" => "Container Variable: $name
				Default Value: $value",
				"type" => "Variable",
				"display" => "",
				"required" => "false",
				"mask" => "false"
			]);
			$service->xml = $service->xml."<Variable>;
	<Value>$value</Value>
	<Name>$name</Name>
	<Mode/>
</Variable>";
		}
		$service->xml = $service->xml."</Environment>";
	}
}

function ParseConfigFields($service)
{
	if(property_exists($service, "configfields"))
	{
		foreach($service->configfields as $configfield)
		{
		$service->xml = $service->xml."<Config Name=\"$configfield->name\" Target=\"$configfield->target\" Default=\"$configfield->default\" Mode=\"$configfield->mode\" Description=\"$configfield->description\" Type=\"$configfield->type\" Display=\"$configfield->display\" Required=\"$configfield->required\" Mask=\"$configfield->mask\">$configfield->value</Config>";
		}
	}
}
?>
