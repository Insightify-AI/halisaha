<?php
class WeatherService {
    public static function getWeather($city, $date) {
        // Gerçek API yerine Mock Data döndürüyoruz
        // Örnek: OpenWeatherMap API entegrasyonu buraya yapılabilir.
        
        $forecasts = [
            'sunny' => ['icon' => 'fas fa-sun', 'text' => 'Güneşli', 'temp' => 25, 'color' => 'text-warning'],
            'cloudy' => ['icon' => 'fas fa-cloud', 'text' => 'Parçalı Bulutlu', 'temp' => 22, 'color' => 'text-secondary'],
            'rainy' => ['icon' => 'fas fa-cloud-rain', 'text' => 'Yağmurlu', 'temp' => 18, 'color' => 'text-primary'],
            'storm' => ['icon' => 'fas fa-bolt', 'text' => 'Fırtınalı', 'temp' => 15, 'color' => 'text-dark']
        ];

        // Rastgele bir hava durumu seç (Tarihe göre sabit olması için hash kullanıyoruz)
        $hash = crc32($city . $date);
        $keys = array_keys($forecasts);
        $randomKey = $keys[$hash % count($keys)];
        
        return $forecasts[$randomKey];
    }
}
?>
