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
                <th>Party</th>
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
            @foreach($partyDetails as $party)
            <tr>
                {{-- <td>{{ $leader['leaderId'] }}</td>
                <td>{{ $leader['profileImage'] }}</td> --}}
                <td>{{ $party['partyname'] }}</td>
                <td>{{ $party['followersCount'] }}</td>
                <td>{{ $party['youngUsers'] }}</td>
                <td>{{ $party['middledAgeUsers'] }}</td>
                <td>{{ $party['maleFollowers'] }}</td>
                <td>{{ $party['femaleFollowers'] }}</td>
                <td>{{ $party['transgenderFollowers'] }}</td>
                <td>{{ $party['appreciatePostCount'] }}</td>
                <td>{{ $party['likePostCount'] }}</td>
                <td>{{ $party['carePostCount'] }}</td>
                <td>{{ $party['unlikesPostCount'] }}</td>
                <td>{{ $party['sadPostCount'] }}</td>
                <td>{{ $party['issuedResolvedCount'] }}</td>
                <td>{{ $party['postFrequency'] }}</td>
                <td>{{ $party['sentiments'] }}</td>
                <td>{{ $party['responseTime'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>