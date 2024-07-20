<!DOCTYPE html>
<html>
<head>
    <title>Laravel 10 Generate PDF Example - fundaofwebit.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Analysis Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <table class="table table-bordered">
        <thead>
            <tr>
                {{-- <th>ID</th>
                <th>ProfileImage</th> --}}
                <th>LeaderName</th>
                <th>LeaderParty</th>
                <th>FollowerCounts</th>
                <th>YoungUsers</th>
                <th>MiddledAgeUsers</th>
                <th>MaleFollowers</th>
                <th>FemaleFollowers</th>
                <th>TransgenderFollowers</th>
                <th>AppreciatePostCount</th>
                <th>LikePostCount</th>
                <th>CarePostCount</th>
                <th>UnlikesPostCount</th>
                <th>SadPostCount</th>
                <th>IssuedResolved</th>
                <th>PostFrequency</th>
                <th>Sentiments</th>
                <th>ResponseTime</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leadersDetails as $leader)
            <tr>
                {{-- <td>{{ $leader['leaderId'] }}</td>
                <td>{{ $leader['profileImage'] }}</td> --}}
                <td>{{ $leader['leaderName'] }}</td>
                <td>{{ $leader['leaderParty'] }}</td>
                <td>{{ $leader['followersCount'] }}</td>
                <td>{{ $leader['youngUsers'] }}</td>
                <td>{{ $leader['middledAgeUsers'] }}</td>
                <td>{{ $leader['maleFollowers'] }}</td>
                <td>{{ $leader['femaleFollowers'] }}</td>
                <td>{{ $leader['transgenderFollowers'] }}</td>
                <td>{{ $leader['appreciatePostCount'] }}</td>
                <td>{{ $leader['likePostCount'] }}</td>
                <td>{{ $leader['carePostCount'] }}</td>
                <td>{{ $leader['unlikesPostCount'] }}</td>
                <td>{{ $leader['sadPostCount'] }}</td>
                <td>{{ $leader['issuedResolvedCount'] }}</td>
                <td>{{ $leader['postFrequency'] }}</td>
                <td>{{ $leader['sentiments'] }}</td>
                <td>{{ $leader['responseTime'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>